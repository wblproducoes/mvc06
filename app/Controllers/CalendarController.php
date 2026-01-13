<?php
/**
 * Controller do sistema de calendário/agenda
 * 
 * @package App\Controllers
 * @author Sistema Administrativo MVC
 */

namespace App\Controllers;

use App\Models\CalendarEvent;
use App\Core\Security;
use App\Core\Logger;
use App\Core\ApiResponse;

class CalendarController extends BaseController
{
    private CalendarEvent $calendarModel;
    
    public function __construct()
    {
        parent::__construct();
        
        // Verifica se usuário está autenticado
        if (!$this->isAuthenticated()) {
            $this->redirect('/login');
            return;
        }
        
        $this->calendarModel = new CalendarEvent();
    }
    
    /**
     * Página principal do calendário
     * 
     * @return void
     */
    public function index(): void
    {
        $user = $this->getCurrentUser();
        
        // Busca configurações do usuário
        $settings = $this->getUserCalendarSettings($user['id']);
        
        // Busca próximos eventos para sidebar
        $upcomingEvents = $this->calendarModel->getUpcomingEvents($user['id'], 5);
        
        // Busca convites pendentes
        $pendingInvites = $this->calendarModel->getPendingInvites($user['id']);
        
        $this->render('calendar/index.twig', [
            'title' => 'Calendário',
            'settings' => $settings,
            'upcoming_events' => $upcomingEvents,
            'pending_invites_count' => count($pendingInvites),
            'colors' => CalendarEvent::COLORS,
            'recurrence_types' => CalendarEvent::RECURRENCE_TYPES
        ]);
    }
    
    /**
     * API para eventos do FullCalendar
     * 
     * @return void
     */
    public function events(): void
    {
        $user = $this->getCurrentUser();
        $start = $_GET['start'] ?? date('Y-m-01');
        $end = $_GET['end'] ?? date('Y-m-t');
        
        try {
            $events = $this->calendarModel->getEventsForFullCalendar($user['id'], $start, $end);
            
            header('Content-Type: application/json');
            echo json_encode($events);
            
        } catch (\Exception $e) {
            Logger::channel(Logger::CHANNEL_ERROR)->error('Error fetching calendar events', [
                'user_id' => $user['id'],
                'error' => $e->getMessage()
            ]);
            
            http_response_code(500);
            echo json_encode(['error' => 'Erro ao carregar eventos']);
        }
    }
    
    /**
     * Detalhes de um evento
     * 
     * @param int $id
     * @return void
     */
    public function show(int $id): void
    {
        $user = $this->getCurrentUser();
        $event = $this->calendarModel->findById($id);
        
        if (!$event) {
            $this->json(['success' => false, 'error' => 'Evento não encontrado'], 404);
            return;
        }
        
        // Verifica se usuário pode ver o evento
        if (!$this->canViewEvent($event, $user['id'])) {
            $this->json(['success' => false, 'error' => 'Sem permissão para ver este evento'], 403);
            return;
        }
        
        // Busca participantes
        $participants = $this->calendarModel->getEventParticipants($id);
        
        $this->json([
            'success' => true,
            'event' => $event,
            'participants' => $participants,
            'can_edit' => $event['user_id'] == $user['id']
        ]);
    }
    
    /**
     * Cria novo evento
     * 
     * @return void
     */
    public function store(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['success' => false, 'error' => 'Método não permitido'], 405);
            return;
        }
        
        $user = $this->getCurrentUser();
        $data = $this->getJsonInput();
        
        try {
            // Adiciona ID do usuário
            $data['user_id'] = $user['id'];
            
            // Sanitiza dados
            $data = $this->sanitizeEventData($data);
            
            // Cria evento
            $eventId = $this->calendarModel->createEvent($data);
            
            $this->json([
                'success' => true,
                'message' => 'Evento criado com sucesso',
                'event_id' => $eventId
            ]);
            
        } catch (\Exception $e) {
            Logger::channel(Logger::CHANNEL_ERROR)->error('Error creating calendar event', [
                'user_id' => $user['id'],
                'error' => $e->getMessage(),
                'data' => $data
            ]);
            
            $this->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 400);
        }
    }
    
    /**
     * Atualiza evento
     * 
     * @param int $id
     * @return void
     */
    public function update(int $id): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['success' => false, 'error' => 'Método não permitido'], 405);
            return;
        }
        
        $user = $this->getCurrentUser();
        $data = $this->getJsonInput();
        
        try {
            // Sanitiza dados
            $data = $this->sanitizeEventData($data);
            
            // Atualiza evento
            $success = $this->calendarModel->updateEvent($id, $data, $user['id']);
            
            if ($success) {
                $this->json([
                    'success' => true,
                    'message' => 'Evento atualizado com sucesso'
                ]);
            } else {
                $this->json([
                    'success' => false,
                    'error' => 'Erro ao atualizar evento'
                ], 400);
            }
            
        } catch (\Exception $e) {
            Logger::channel(Logger::CHANNEL_ERROR)->error('Error updating calendar event', [
                'event_id' => $id,
                'user_id' => $user['id'],
                'error' => $e->getMessage()
            ]);
            
            $this->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 400);
        }
    }
    
    /**
     * Atualiza apenas datas do evento (drag & drop)
     * 
     * @param int $id
     * @return void
     */
    public function updateDates(int $id): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['success' => false, 'error' => 'Método não permitido'], 405);
            return;
        }
        
        $user = $this->getCurrentUser();
        $data = $this->getJsonInput();
        
        if (empty($data['start']) || empty($data['end'])) {
            $this->json(['success' => false, 'error' => 'Datas são obrigatórias'], 400);
            return;
        }
        
        try {
            $success = $this->calendarModel->updateEventDates(
                $id,
                $data['start'],
                $data['end'],
                $user['id']
            );
            
            if ($success) {
                $this->json([
                    'success' => true,
                    'message' => 'Evento movido com sucesso'
                ]);
            } else {
                $this->json([
                    'success' => false,
                    'error' => 'Erro ao mover evento'
                ], 400);
            }
            
        } catch (\Exception $e) {
            Logger::channel(Logger::CHANNEL_ERROR)->error('Error updating event dates', [
                'event_id' => $id,
                'user_id' => $user['id'],
                'error' => $e->getMessage()
            ]);
            
            $this->json([
                'success' => false,
                'error' => 'Erro interno do servidor'
            ], 500);
        }
    }
    
    /**
     * Exclui evento
     * 
     * @param int $id
     * @return void
     */
    public function destroy(int $id): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['success' => false, 'error' => 'Método não permitido'], 405);
            return;
        }
        
        $user = $this->getCurrentUser();
        
        try {
            $success = $this->calendarModel->softDelete($id, $user['id']);
            
            if ($success) {
                $this->json([
                    'success' => true,
                    'message' => 'Evento excluído com sucesso'
                ]);
            } else {
                $this->json([
                    'success' => false,
                    'error' => 'Erro ao excluir evento ou sem permissão'
                ], 400);
            }
            
        } catch (\Exception $e) {
            Logger::channel(Logger::CHANNEL_ERROR)->error('Error deleting calendar event', [
                'event_id' => $id,
                'user_id' => $user['id'],
                'error' => $e->getMessage()
            ]);
            
            $this->json([
                'success' => false,
                'error' => 'Erro interno do servidor'
            ], 500);
        }
    }
    
    /**
     * Lista convites pendentes
     * 
     * @return void
     */
    public function invites(): void
    {
        $user = $this->getCurrentUser();
        $pendingInvites = $this->calendarModel->getPendingInvites($user['id']);
        
        $this->render('calendar/invites.twig', [
            'title' => 'Convites Pendentes',
            'invites' => $pendingInvites
        ]);
    }
    
    /**
     * Responde a convite
     * 
     * @param int $id
     * @return void
     */
    public function respondInvite(int $id): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['success' => false, 'error' => 'Método não permitido'], 405);
            return;
        }
        
        $user = $this->getCurrentUser();
        $data = $this->getJsonInput();
        
        if (!in_array($data['status'] ?? '', ['accepted', 'declined'])) {
            $this->json(['success' => false, 'error' => 'Status inválido'], 400);
            return;
        }
        
        try {
            $success = $this->calendarModel->respondToInvite($id, $user['id'], $data['status']);
            
            if ($success) {
                $statusText = $data['status'] === 'accepted' ? 'aceito' : 'recusado';
                $this->json([
                    'success' => true,
                    'message' => "Convite {$statusText} com sucesso"
                ]);
            } else {
                $this->json([
                    'success' => false,
                    'error' => 'Convite não encontrado'
                ], 404);
            }
            
        } catch (\Exception $e) {
            Logger::channel(Logger::CHANNEL_ERROR)->error('Error responding to invite', [
                'event_id' => $id,
                'user_id' => $user['id'],
                'status' => $data['status'],
                'error' => $e->getMessage()
            ]);
            
            $this->json([
                'success' => false,
                'error' => 'Erro interno do servidor'
            ], 500);
        }
    }
    
    /**
     * Próximos eventos (para widgets)
     * 
     * @return void
     */
    public function upcoming(): void
    {
        $user = $this->getCurrentUser();
        $limit = (int)($_GET['limit'] ?? 5);
        
        try {
            $events = $this->calendarModel->getUpcomingEvents($user['id'], $limit);
            
            $this->json([
                'success' => true,
                'events' => $events
            ]);
            
        } catch (\Exception $e) {
            Logger::channel(Logger::CHANNEL_ERROR)->error('Error fetching upcoming events', [
                'user_id' => $user['id'],
                'error' => $e->getMessage()
            ]);
            
            $this->json([
                'success' => false,
                'error' => 'Erro ao carregar próximos eventos'
            ], 500);
        }
    }
    
    /**
     * Retorna dados JSON de entrada
     * 
     * @return array
     */
    private function getJsonInput(): array
    {
        $input = file_get_contents('php://input');
        return json_decode($input, true) ?? [];
    }
    
    /**
     * Sanitiza dados do evento
     * 
     * @param array $data
     * @return array
     */
    private function sanitizeEventData(array $data): array
    {
        return [
            'title' => Security::sanitizeInput($data['title'] ?? '', 'string'),
            'description' => Security::sanitizeInput($data['description'] ?? '', 'string'),
            'start_date' => Security::sanitizeInput($data['start_date'] ?? '', 'string'),
            'end_date' => Security::sanitizeInput($data['end_date'] ?? '', 'string'),
            'all_day' => (bool)($data['all_day'] ?? false),
            'color' => Security::sanitizeInput($data['color'] ?? '#007bff', 'string'),
            'location' => Security::sanitizeInput($data['location'] ?? '', 'string'),
            'is_public' => (bool)($data['is_public'] ?? false),
            'reminder_minutes' => !empty($data['reminder_minutes']) ? (int)$data['reminder_minutes'] : null,
            'recurrence' => Security::sanitizeInput($data['recurrence'] ?? 'none', 'string'),
            'recurrence_end' => Security::sanitizeInput($data['recurrence_end'] ?? '', 'string') ?: null,
            'participants' => array_map('intval', $data['participants'] ?? [])
        ];
    }
    
    /**
     * Verifica se usuário pode ver o evento
     * 
     * @param array $event
     * @param int $userId
     * @return bool
     */
    private function canViewEvent(array $event, int $userId): bool
    {
        // Criador pode ver
        if ($event['user_id'] == $userId) {
            return true;
        }
        
        // Evento público pode ser visto por todos
        if ($event['is_public']) {
            return true;
        }
        
        // Verifica se é participante
        $participant = $this->database->fetchOne(
            "SELECT 1 FROM {prefix}calendar_event_participants 
             WHERE event_id = ? AND user_id = ?",
            [$event['id'], $userId]
        );
        
        return !empty($participant);
    }
    
    /**
     * Busca configurações do calendário do usuário
     * 
     * @param int $userId
     * @return array
     */
    private function getUserCalendarSettings(int $userId): array
    {
        $settings = $this->database->fetchOne(
            "SELECT * FROM {prefix}calendar_settings WHERE user_id = ?",
            [$userId]
        );
        
        // Retorna configurações padrão se não existir
        if (!$settings) {
            return [
                'default_view' => 'dayGridMonth',
                'default_color' => '#007bff',
                'show_weekends' => true,
                'start_time' => '08:00:00',
                'end_time' => '18:00:00',
                'timezone' => 'America/Sao_Paulo',
                'email_notifications' => true,
                'reminder_default' => 15
            ];
        }
        
        return $settings;
    }
}