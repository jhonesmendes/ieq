#!/usr/bin/env bash
# Migra prefixos /ieq/ em strings de .php, .js e .html.
# Requisitos: Bash, Python >= 3.8 e Pygments (testado com 2.19.2).
# Debian/Ubuntu: sudo apt-get install python3 python3-pygments
#
# Simular: bash migrar_ieq_para_raiz.sh
# Aplicar: sudo bash migrar_ieq_para_raiz.sh --apply
# Outro destino: TARGET_DIR=/caminho/do/site bash migrar_ieq_para_raiz.sh
# Backup: BACKUP_DIR=/caminho/privado sudo -E bash migrar_ieq_para_raiz.sh --apply
#
# Revise o diff antes de aplicar. Execute sem deploys/edicoes concorrentes.
# Comentarios, prosa e URLs completas nao sao alvos da substituicao.
# Strings iniciadas por /ieq/ sao candidatas: seu significado exige revisao.
# Caminhos em CSS, escapados, dinamicos ou dentro de outras strings
# ficam para revisao manual. O relatorio lista as ocorrencias restantes.
# includes('/ieq/') vira includes('/'): a condicao passa a ser quase sempre
# verdadeira em um pathname. Revise a logica do logout.
set -euo pipefail
umask 077

MODE="${1:---dry-run}"
if [[ $# -gt 1 || ( "$MODE" != '--dry-run' && "$MODE" != '--apply' ) ]]; then
    printf 'Uso: bash %s [--dry-run|--apply]\n' "$0" >&2
    exit 2
fi

PYTHON_BIN="${PYTHON_BIN:-python3}"
command -v "$PYTHON_BIN" >/dev/null || { printf 'Python 3 nao encontrado.\n' >&2; exit 1; }

"$PYTHON_BIN" - "$MODE" \
    "${TARGET_DIR:-/www/wwwroot/www.ieqroo.com.br}" \
    "${BACKUP_DIR:-/www/backups/ieq-migration}" <<'PY'
import difflib
import hashlib
import json
import os
from pathlib import Path
import shlex
import shutil
import stat
import sys
import tarfile
import tempfile
import time
from datetime import datetime, timezone

sys.stdout.reconfigure(errors='backslashreplace')
sys.stderr.reconfigure(errors='backslashreplace')

try:
    from pygments.lexers import HtmlLexer, HtmlPhpLexer, JavascriptLexer
    from pygments.token import Comment, Error, Name, Operator, String
except ImportError:
    sys.exit('Instale o Pygments: sudo apt-get install python3-pygments')

if sys.version_info < (3, 8):
    sys.exit('E necessario Python >= 3.8.')

mode, target, backup_base = sys.argv[1:]
root = Path(target).resolve(strict=True)
if not root.is_dir() or root == Path(root.anchor):
    sys.exit('TARGET_DIR deve ser a pasta especifica do site, nunca /.')


def rewrite(raw, suffix, label='<arquivo>'):
    # Latin-1 faz um mapeamento byte a byte; nao converte o encoding original.
    source = raw.decode('latin-1')
    lexer = {'.php': HtmlPhpLexer, '.html': HtmlLexer, '.js': JavascriptLexer}[suffix]()
    tokens = list(lexer.get_tokens_unprocessed(source))
    cursor = 0
    in_style = False
    for start, token, value in tokens:
        if start != cursor or source[start:start + len(value)] != value:
            raise ValueError('Tokenizacao sem correspondencia exata com o arquivo.')
        if token == Name.Tag and value.lower() == 'style':
            in_style = source[max(0, start - 2):start] != '</'
        if token in Error:
            line = source.count('\n', 0, start) + 1
            if in_style:
                print('AVISO: {}:{}: token CSS nao reconhecido, preservado.'.format(label, line), file=sys.stderr)
            else:
                raise ValueError('Token nao reconhecido na linha {}; revise manualmente.'.format(line))
        cursor += len(value)
    if cursor != len(source):
        raise ValueError('Tokenizacao incompleta; nenhuma alteracao foi aplicada.')

    offsets = []
    previous = None
    significant = []
    php = False
    html_comment = False
    in_style = False
    url_attributes = {'href', 'src', 'action', 'formaction', 'poster', 'data', 'cite', 'background', 'manifest'}
    for start, token, value in tokens:
        if token in Comment.Preproc:
            if value.startswith('<?'):
                php = True
            elif value == '?>':
                php = False
        if not php and token == Comment.Multiline:
            if value.startswith('<!--'):
                html_comment = True
            if html_comment and '-->' in value:
                html_comment = False
            previous = (start, token, value)
            continue
        if token == Name.Tag and value.lower() == 'style':
            in_style = source[max(0, start - 2):start] != '</'
        offset = None
        if not html_comment and not in_style:
            quote = {String.Single: "'", String.Double: '"'}.get(token)
            if quote and value.startswith(quote + '/ieq/'):
                offset = start + 1
            elif token in (String.Double, String.Backtick) and value.startswith('/ieq/') and previous:
                prev_start, prev_token, prev_value = previous
                opener = '"' if token == String.Double else '`'
                if prev_token == token and prev_value == opener and prev_start + 1 == start:
                    offset = start
            elif token == String and len(significant) >= 2:
                # HTML: apenas atributos de URL; preserva title, alt, texto etc.
                (_, attr_token, attr_value), (_, eq_token, eq_value) = significant[-2:]
                if (attr_token == Name.Attribute and attr_value.strip().lower() in url_attributes
                        and eq_token == Operator and eq_value == '='):
                    quoted = value[:1] in ("'", '"')
                    if value[int(quoted):].startswith('/ieq/'):
                        offset = start + int(quoted)
        if offset is not None:
            offsets.append(offset)
        previous = (start, token, value)
        if value.strip():
            significant.append(previous)
            significant = significant[-2:]

    for offset in reversed(offsets):
        # Remover /ieq cobre tambem /ieq/api/, sem aplicar duas trocas.
        source = source[:offset] + source[offset + 4:]
    return source.encode('latin-1')


def digest(data):
    return hashlib.sha256(data).hexdigest()


def fail_walk(error):
    raise error


def unchanged(path, expected):
    if path.is_symlink() or path.resolve(strict=True) != path:
        raise RuntimeError('Caminho mudou ou virou link: ' + str(path))
    if not stat.S_ISREG(path.stat().st_mode) or path.read_bytes() != expected:
        raise RuntimeError('Arquivo mudou desde a simulacao: ' + str(path))


def atomic_write(path, data):
    original_stat = path.stat()
    # Mesmo filesystem para rename atomico; pasta privada para nao expor PHP.
    staging = tempfile.mkdtemp(prefix='.ieq-migrate-', dir=str(path.parent))
    temporary = os.path.join(staging, 'conteudo')
    try:
        with open(temporary, 'xb') as output:
            output.write(data)
            output.flush()
            if hasattr(os, 'fchown'):
                os.fchown(output.fileno(), original_stat.st_uid, original_stat.st_gid)
            shutil.copystat(str(path), temporary)
            modified = time.time_ns()
            if modified // 10**9 == original_stat.st_mtime_ns // 10**9:
                modified += 10**9
            os.utime(temporary, ns=(original_stat.st_atime_ns, modified))
            os.fsync(output.fileno())
        os.replace(temporary, str(path))
    finally:
        if os.path.exists(temporary):
            os.unlink(temporary)
        os.rmdir(staging)


backup = None
try:
    plan = []
    patches = []
    leftovers = []
    skipped_links = 0
    for directory, dirs, files in os.walk(str(root), followlinks=False, onerror=fail_walk):
        skipped_links += sum(Path(directory, d).is_symlink() for d in dirs)
        dirs[:] = sorted(d for d in dirs if not Path(directory, d).is_symlink())
        for name in sorted(files):
            path = Path(directory, name)
            if path.suffix.lower() not in ('.php', '.js', '.html'):
                continue
            if path.is_symlink():
                skipped_links += 1
                continue
            if not stat.S_ISREG(path.stat().st_mode):
                raise RuntimeError('Arquivo especial encontrado: ' + str(path))
            before = path.read_bytes()
            if b'/ieq/' not in before:
                continue
            relative = path.relative_to(root).as_posix()
            try:
                after = rewrite(before, path.suffix.lower(), relative)
            except Exception as exc:
                raise RuntimeError(str(path) + ': ' + str(exc)) from exc
            for line_number, line in enumerate(after.splitlines(), 1):
                if b'/ieq/' in line:
                    leftovers.append('{}:{}'.format(relative, line_number))
            if after == before:
                continue
            if path.stat().st_nlink != 1:
                raise RuntimeError('Hard link exige revisao manual: ' + str(path))
            plan.append((path, relative, before, after))
            # Apenas a exibicao usa UTF-8; os arquivos mantem seus bytes.
            file_diff = difflib.unified_diff(
                before.decode('utf-8', errors='replace').splitlines(keepends=True),
                after.decode('utf-8', errors='replace').splitlines(keepends=True),
                fromfile='a/' + relative, tofile='b/' + relative)
            for diff_line in file_diff:
                patches.append(diff_line if diff_line.endswith('\n')
                               else diff_line + '\n\\ No newline at end of file\n')

    diff = ''.join(patches)
    print(diff, end='' if diff.endswith('\n') else '\n')
    print('\nArquivos a alterar: {}. Links ignorados: {}.'.format(len(plan), skipped_links))
    if leftovers:
        print('Ocorrencias preservadas para revisao (inclui comentarios e URLs completas):')
        print('\n'.join(leftovers))
    if not plan:
        print('Nenhuma alteracao necessaria nos padroes suportados.')
        sys.exit(0)
    if mode == '--dry-run':
        print('\nSIMULACAO: nenhum arquivo foi alterado. Revise o diff e use --apply.')
        sys.exit(0)

    backup_parent = Path(backup_base).resolve()
    public_parent = Path('/www/wwwroot').resolve()
    for forbidden in (root, public_parent):
        if backup_parent == forbidden or forbidden in backup_parent.parents:
            raise RuntimeError('BACKUP_DIR precisa ficar fora da pasta publica: ' + str(forbidden))
    backup_parent.mkdir(mode=0o700, parents=True, exist_ok=True)
    stamp = datetime.now(timezone.utc).strftime('%Y%m%dT%H%M%SZ-')
    backup = Path(tempfile.mkdtemp(prefix=stamp, dir=str(backup_parent)))
    archive = backup / 'originais.tar.gz'
    (backup / 'alteracoes.diff').write_text(diff, encoding='utf-8')
    manifest = {'target': str(root), 'files': {
        relative: {'before_sha256': digest(before), 'after_sha256': digest(after)}
        for _, relative, before, after in plan}}
    (backup / 'manifest.json').write_text(json.dumps(manifest, indent=2), encoding='utf-8')

    # Todos os originais sao copiados ANTES da primeira substituicao.
    with tarfile.open(str(archive), 'w:gz') as tar:
        for path, relative, before, _ in plan:
            unchanged(path, before)
            tar.add(str(path), arcname=relative, recursive=False)
    with tarfile.open(str(archive), 'r:gz') as tar:
        for _, relative, before, _ in plan:
            member = tar.extractfile(relative)
            if member is None or member.read() != before:
                raise RuntimeError('Backup diverge do original: ' + relative)
    with archive.open('rb+') as saved:
        os.fsync(saved.fileno())

    restore = 'sudo tar -xzpf {} -C {}'.format(shlex.quote(str(archive)), shlex.quote(str(root)))
    (backup / 'RESTAURAR.txt').write_text(restore + '\n', encoding='utf-8')
    print('\nBackup verificado: ' + str(archive), flush=True)
    print('Para restaurar os arquivos alterados:\n' + restore, flush=True)
    for path, _, before, _ in plan:
        unchanged(path, before)
    for path, relative, before, after in plan:
        unchanged(path, before)
        atomic_write(path, after)
        if path.read_bytes() != after:
            raise RuntimeError('Falha ao verificar gravacao: ' + relative)
        print('Alterado: ' + relative, flush=True)
    print('\nConcluido. Backup: ' + str(backup))
except Exception as exc:
    print('\nERRO: ' + str(exc), file=sys.stderr)
    if backup is not None:
        print('Backup/relatorios: ' + str(backup), file=sys.stderr)
        print('Se a gravacao ja comecou, consulte RESTAURAR.txt para desfazer.', file=sys.stderr)
    sys.exit(1)
PY
