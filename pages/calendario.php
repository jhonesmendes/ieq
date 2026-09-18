<?php
require_once 'config/eventos.php';

$user_funcao = $_SESSION['user_funcao'];
?>

<div class="page-header">
    <h1>📅 Calendário de Eventos</h1>
    <p>Visualize todos os eventos da igreja em um calendário interativo</p>
</div>

<div class="calendar-container">
    <div id="calendar"></div>
</div>

<!-- Modal Detalhes do Evento -->
<div id="modalEventoDetalhes" class="modal">
    <div class="modal-content modal-medium">
        <span class="close">&times;</span>
        <h2 id="eventoTitulo"></h2>
        
        <div class="evento-detalhes">
            <div class="detalhe-item">
                <span class="detalhe-icon">📅</span>
                <div>
                    <strong>Data e Hora</strong>
                    <p id="eventoDataHora"></p>
                </div>
            </div>
            
            <div class="detalhe-item">
                <span class="detalhe-icon">📍</span>
                <div>
                    <strong>Local</strong>
                    <p id="eventoLocal"></p>
                </div>
            </div>
            
            <div class="detalhe-item">
                <span class="detalhe-icon">📝</span>
                <div>
                    <strong>Descrição</strong>
                    <p id="eventoDescricao"></p>
                </div>
            </div>
            
            <div class="detalhe-item">
                <span class="detalhe-icon">🎟️</span>
                <div>
                    <strong>Vagas</strong>
                    <p id="eventoVagas"></p>
                </div>
            </div>
            
            <div class="detalhe-item">
                <span class="detalhe-icon">🏷️</span>
                <div>
                    <strong>Tipo</strong>
                    <p id="eventoTipo"></p>
                </div>
            </div>
        </div>
        
        <?php if (in_array($user_funcao, ['admin', 'pastor'])): ?>
        <div class="modal-actions">
            <button class="btn btn-primary" onclick="editarEventoCalendario()">✏️ Editar</button>
            <button class="btn btn-danger" onclick="deletarEventoCalendario()">🗑️ Deletar</button>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- FullCalendar CSS -->
<link href='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.css' rel='stylesheet' />

<style>
    .page-header {
        margin-bottom: 30px;
    }

    .page-header h1 {
        font-size: 32px;
        color: #333;
        margin-bottom: 10px;
    }

    .page-header p {
        color: #666;
        font-size: 16px;
    }

    .calendar-container {
        background: white;
        padding: 30px;
        border-radius: 12px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.08);
    }

    #calendar {
        max-width: 1200px;
        margin: 0 auto;
    }

    /* Personalizar FullCalendar */
    .fc .fc-button-primary {
        background-color: #4e73df;
        border-color: #4e73df;
    }

    .fc .fc-button-primary:hover {
        background-color: #2e59d9;
        border-color: #2e59d9;
    }

    .fc .fc-button-primary:not(:disabled).fc-button-active {
        background-color: #224abe;
        border-color: #224abe;
    }

    .fc-event {
        cursor: pointer;
        border-radius: 4px;
        padding: 2px 4px;
    }

    .fc-event:hover {
        opacity: 0.85;
    }

    /* Modal de Detalhes */
    .modal-medium {
        max-width: 600px;
    }

    .evento-detalhes {
        display: flex;
        flex-direction: column;
        gap: 20px;
        margin: 25px 0;
    }

    .detalhe-item {
        display: flex;
        align-items: flex-start;
        gap: 15px;
        padding: 15px;
        background: #f8f9fa;
        border-radius: 8px;
    }

    .detalhe-icon {
        font-size: 24px;
        width: 40px;
        height: 40px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: white;
        border-radius: 8px;
        flex-shrink: 0;
    }

    .detalhe-item div {
        flex: 1;
    }

    .detalhe-item strong {
        display: block;
        color: #333;
        font-size: 14px;
        margin-bottom: 5px;
    }

    .detalhe-item p {
        margin: 0;
        color: #666;
        font-size: 15px;
    }

    .modal-actions {
        display: flex;
        gap: 10px;
        justify-content: flex-end;
        margin-top: 20px;
        padding-top: 20px;
        border-top: 1px solid #e0e0e0;
    }

    .btn-danger {
        background: #dc3545;
        color: white;
        border: none;
        padding: 10px 20px;
        border-radius: 6px;
        cursor: pointer;
        font-size: 14px;
    }

    .btn-danger:hover {
        background: #c82333;
    }
</style>

<!-- FullCalendar JS -->
<script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js'></script>
<script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/locales/pt-br.global.min.js'></script>

<script>
    let calendar;
    let eventoSelecionado = null;

    document.addEventListener('DOMContentLoaded', function() {
        const calendarEl = document.getElementById('calendar');
        
        calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: 'dayGridMonth',
            locale: 'pt-br',
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'dayGridMonth,timeGridWeek,timeGridDay'
            },
            buttonText: {
                today: 'Hoje',
                month: 'Mês',
                week: 'Semana',
                day: 'Dia'
            },
            events: function(info, successCallback, failureCallback) {
                carregarEventosCalendario(successCallback, failureCallback);
            },
            eventClick: function(info) {
                mostrarDetalhesEvento(info.event);
            },
            height: 'auto'
        });
        
        calendar.render();

        // Fechar modal
        document.querySelector('#modalEventoDetalhes .close').onclick = function() {
            document.getElementById('modalEventoDetalhes').style.display = 'none';
        };
    });

    async function carregarEventosCalendario(successCallback, failureCallback) {
        try {
            const resposta = await fazerRequisicao('listar_eventos', 'GET');
            
            if (resposta.status === 'sucesso') {
                const eventos = resposta.dados.map(evento => {
                    // Cores por tipo de evento
                    const cores = {
                        'culto': '#4e73df',
                        'retiro': '#1cc88a',
                        'conferencia': '#f6c23e',
                        'congresso': '#e74a3b',
                        'reuniao': '#36b9cc'
                    };
                    
                    return {
                        id: evento.id,
                        title: evento.nome,
                        start: evento.data_evento,
                        backgroundColor: cores[evento.tipo] || '#858796',
                        borderColor: cores[evento.tipo] || '#858796',
                        extendedProps: {
                            tipo: evento.tipo,
                            localizacao: evento.localizacao,
                            descricao: evento.descricao,
                            vagas: evento.vagas
                        }
                    };
                });
                
                successCallback(eventos);
            } else {
                failureCallback();
            }
        } catch (erro) {
            console.error('Erro ao carregar eventos:', erro);
            failureCallback();
        }
    }

    function mostrarDetalhesEvento(event) {
        eventoSelecionado = event;
        
        document.getElementById('eventoTitulo').textContent = event.title;
        
        const dataFormatada = new Date(event.start).toLocaleString('pt-BR', {
            day: '2-digit',
            month: 'long',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
        document.getElementById('eventoDataHora').textContent = dataFormatada;
        
        document.getElementById('eventoLocal').textContent = event.extendedProps.localizacao || 'Não informado';
        document.getElementById('eventoDescricao').textContent = event.extendedProps.descricao || 'Sem descrição';
        document.getElementById('eventoVagas').textContent = event.extendedProps.vagas ? event.extendedProps.vagas + ' vagas' : 'Ilimitado';
        
        const tiposNomes = {
            'culto': 'Culto',
            'retiro': 'Retiro',
            'conferencia': 'Conferência',
            'congresso': 'Congresso',
            'reuniao': 'Reunião'
        };
        document.getElementById('eventoTipo').textContent = tiposNomes[event.extendedProps.tipo] || event.extendedProps.tipo;
        
        document.getElementById('modalEventoDetalhes').style.display = 'block';
    }

    function editarEventoCalendario() {
        if (eventoSelecionado) {
            // Redirecionar para página de eventos com o ID
            window.location.href = 'index.php?page=eventos&editar=' + eventoSelecionado.id;
        }
    }

    async function deletarEventoCalendario() {
        if (!eventoSelecionado) return;
        
        if (confirm('Tem certeza que deseja deletar este evento?')) {
            try {
                const resposta = await fazerRequisicao('deletar_evento', 'POST', { id: eventoSelecionado.id });
                
                if (resposta.status === 'sucesso') {
                    mostrarNotificacao('Evento deletado com sucesso', 'sucesso');
                    document.getElementById('modalEventoDetalhes').style.display = 'none';
                    calendar.refetchEvents(); // Recarregar eventos
                } else {
                    mostrarNotificacao('Erro ao deletar evento', 'erro');
                }
            } catch (erro) {
                mostrarNotificacao('Erro ao deletar evento', 'erro');
            }
        }
    }
</script>
