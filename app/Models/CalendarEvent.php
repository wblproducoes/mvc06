<?php
/**
 * Model para eventos do calendário
 * 
 * @package App\Models
 * @author Sistema Administrativo MVC
 */

namespace App\Models;

use App\Core\Database;
use App\Core\Logger;

class CalendarEvent extends BaseModel
{
    protected string $table = 'calendar_events';
    
    /**
     * Cores disponíveis para eventos
     */
    public const COLORS = [
        '#007bff' => 'Azul',
        '#28a745' => 'Verde',
        '#dc3545' => 'Vermelho',
        '#ffc107' => 'Amarelo',
        '#17a2b8' => 'Ciano',
        '#6f42c1' => 'Roxo',
        '#e83e8c' => 'Rosa',
        '#fd7e14' => 'Laranja',
        '#20c997' => 'Verde Água',
        '#6c757d' => 'Cinza'
    ];
    
    /**
     * Tipos de recorrência
     */
    public const RECURRENCE_TYPES = [
        'none' => 'Sem recorrência',
        'daily' => 'Diariamente',
        'weekly' => 'Semanalmente',
        'monthly' => 'Mensalmente',
        'yearly' => 'Anualmente'
    ];
    
    /**
     * Busca eventos por período e usuário
     * 
     * @param int $userId
     * @param string $startDate
     * @param string $endDate
     * @param bool $includePublic
     * @return array
     */
    public function getEventsByPeriod(int $userId, string $startDate, string $endDate, bool $includePublic = true): array
    {
        $query = "
            SELECT 
                e.*,
                u.name as creator_name,
                (SELECT COUNT(*) FROM {prefix}calendar_event_participants p 
                 WHERE p.event_id = e.id AND p.status = 'accepted') as participants_count
            FROM {prefix}calendar_events e
            LEFT JOIN {prefix}users u ON e.user_id = u.id
            WHERE e.deleted_at IS NULL
            AND (
                (e.start_date BETWEEN ? AND ?) 
                OR (e.end_date BETWEEN ? AND ?)
                OR (e.start_date <= ? AND e.end_date >= ?)
            )
            AND (
                e.user_id = ?
                OR (e.is_public = 1 AND ? = 1)
                OR EXISTS (
                    SELECT 1 FROM {prefix}calendar_event_participants p 
                    WHERE p.event_id = e.id AND p.user_id = ? AND p.status = 'accepted'
                )
            )
            ORDER BY e.start_date ASC
        ";
        
        $params = [
            $startDate, $endDate,
            $startDate, $endDate,
            $startDate, $endDate,
            $userId,
            $includePublic ? 1 : 0,
            $userId
        ];
        
        return $this->database->fetchAll($query, $params);
    }
    
    /**
     * Busca eventos para FullCalendar
     * 
     * @param int $userId
     * @param string $start
     * @param string $end
     * @return array
     */
    public function getEventsForFullCalendar(int $userId, string $start, string $end): array
    {
        $events = $this->getEventsByPeriod($userId, $start, $end);
        $fullCalendarEvents = [];
        
        foreach ($events as $event) {
            $fullCalendarEvents[] = [
                'id' => $event['id'],
                'title' => $event['title'],
                'start' => $event['start_date'],
                'end' => $event['end_date'],
                'allDay' => (bool)$event['all_day'],
                'color' => $event['color'],
                'textColor' => $this->getTextColor($event['color']),
                'extendedProps' => [
                    'description' => $event['description'],
                    'location' => $event['location'],
                    'creator' => $event['creator_name'],
                    'isOwner' => $event['user_id'] == $userId,
                    'isPublic' => (bool)$event['is_public'],
                    'participantsCount' => $event['participants_count'],
                    'recurrence' => $event['recurrence']
                ]
            ];
        }
        
        return $fullCalendarEvents;
    }
    
    /**
     * Cria novo evento
     * 
     * @param array $data
     * @return int
     */
    public function createEvent(array $data): int
    {
        // Validação básica
        $this->validateEventData($data);
        
        // Prepara dados para inserção
        $eventData = [
            'user_id' => $data['user_id'],
            'title' => trim($data['title']),
            'description' => $data['description'] ?? null,
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'],
            'all_day' => $data['all_day'] ?? 0,
            'color' => $data['color'] ?? '#007bff',
            'location' => $data['location'] ?? null,
            'is_public' => $data['is_public'] ?? 0,
            'reminder_minutes' => $data['reminder_minutes'] ?? null,
            'recurrence' => $data['recurrence'] ?? 'none',
            'recurrence_end' => $data['recurrence_end'] ?? null
        ];
        
        $eventId = $this->database->insert($this->getTableName(), $eventData);
        
        // Adiciona participantes se fornecidos
        if (!empty($data['participants'])) {
            $this->addParticipants($eventId, $data['participants'], $data['user_id']);
        }
        
        Logger::channel(Logger::CHANNEL_SYSTEM)->info('Calendar event created', [
            'event_id' => $eventId,
            'user_id' => $data['user_id'],
            'title' => $data['title']
        ]);
        
        return $eventId;
    }
    
    /**
     * Atualiza evento
     * 
     * @param int $id
     * @param array $data
     * @param int $userId
     * @return bool
     */
    public function updateEvent(int $id, array $data, int $userId): bool
    {
        $event = $this->findById($id);
        
        if (!$event || $event['user_id'] != $userId) {
            throw new \Exception('Evento não encontrado ou sem permissão para editar');
        }
        
        // Validação básica
        $this->validateEventData($data);
        
        // Prepara dados para atualização
        $updateData = [];
        $allowedFields = ['title', 'description', 'start_date', 'end_date', 'all_day', 
                         'color', 'location', 'is_public', 'reminder_minutes', 
                         'recurrence', 'recurrence_end'];
        
        foreach ($allowedFields as $field) {
            if (array_key_exists($field, $data)) {
                $updateData[$field] = $data[$field];
            }
        }
        
        $success = $this->database->update($this->getTableName(), $updateData, ['id' => $id]) > 0;
        
        // Atualiza participantes se fornecidos
        if (array_key_exists('participants', $data)) {
            $this->updateParticipants($id, $data['participants'], $userId);
        }
        
        if ($success) {
            Logger::channel(Logger::CHANNEL_SYSTEM)->info('Calendar event updated', [
                'event_id' => $id,
                'user_id' => $userId,
                'updated_fields' => array_keys($updateData)
            ]);
        }
        
        return $success;
    }
    
    /**
     * Atualiza apenas datas do evento (para drag & drop)
     * 
     * @param int $id
     * @param string $startDate
     * @param string $endDate
     * @param int $userId
     * @return bool
     */
    public function updateEventDates(int $id, string $startDate, string $endDate, int $userId): bool
    {
        $event = $this->findById($id);
        
        if (!$event || $event['user_id'] != $userId) {
            return false;
        }
        
        $updateData = [
            'start_date' => $startDate,
            'end_date' => $endDate
        ];
        
        $success = $this->database->update($this->getTableName(), $updateData, ['id' => $id]) > 0;
        
        if ($success) {
            Logger::channel(Logger::CHANNEL_SYSTEM)->info('Calendar event dates updated', [
                'event_id' => $id,
                'user_id' => $userId,
                'new_start' => $startDate,
                'new_end' => $endDate
            ]);
        }
        
        return $success;
    }
    
    /**
     * Adiciona participantes ao evento
     * 
     * @param int $eventId
     * @param array $userIds
     * @param int $invitedBy
     * @return void
     */
    public function addParticipants(int $eventId, array $userIds, int $invitedBy): void
    {
        foreach ($userIds as $userId) {
            if ($userId == $invitedBy) continue; // Não convida a si mesmo
            
            try {
                $this->database->insert('{prefix}calendar_event_participants', [
                    'event_id' => $eventId,
                    'user_id' => $userId,
                    'status' => 'pending',
                    'invited_by' => $invitedBy
                ]);
            } catch (\Exception $e) {
                // Ignora se já existe (unique constraint)
                if (!str_contains($e->getMessage(), 'Duplicate entry')) {
                    throw $e;
                }
            }
        }
    }
    
    /**
     * Atualiza participantes do evento
     * 
     * @param int $eventId
     * @param array $userIds
     * @param int $userId
     * @return void
     */
    public function updateParticipants(int $eventId, array $userIds, int $userId): void
    {
        // Remove participantes não listados
        $this->database->query(
            "DELETE FROM {prefix}calendar_event_participants 
             WHERE event_id = ? AND user_id NOT IN (" . str_repeat('?,', count($userIds) - 1) . "?)",
            array_merge([$eventId], $userIds)
        );
        
        // Adiciona novos participantes
        $this->addParticipants($eventId, $userIds, $userId);
    }
    
    /**
     * Busca participantes do evento
     * 
     * @param int $eventId
     * @return array
     */
    public function getEventParticipants(int $eventId): array
    {
        return $this->database->fetchAll(
            "SELECT p.*, u.name as user_name, u.email as user_email,
                    ib.name as invited_by_name
             FROM {prefix}calendar_event_participants p
             LEFT JOIN {prefix}users u ON p.user_id = u.id
             LEFT JOIN {prefix}users ib ON p.invited_by = ib.id
             WHERE p.event_id = ?
             ORDER BY p.status, u.name",
            [$eventId]
        );
    }
    
    /**
     * Responde a convite
     * 
     * @param int $eventId
     * @param int $userId
     * @param string $status
     * @return bool
     */
    public function respondToInvite(int $eventId, int $userId, string $status): bool
    {
        if (!in_array($status, ['accepted', 'declined'])) {
            return false;
        }
        
        $success = $this->database->update(
            '{prefix}calendar_event_participants',
            [
                'status' => $status,
                'responded_at' => date('Y-m-d H:i:s')
            ],
            [
                'event_id' => $eventId,
                'user_id' => $userId
            ]
        ) > 0;
        
        if ($success) {
            Logger::channel(Logger::CHANNEL_SYSTEM)->info('Calendar invite responded', [
                'event_id' => $eventId,
                'user_id' => $userId,
                'status' => $status
            ]);
        }
        
        return $success;
    }
    
    /**
     * Busca convites pendentes do usuário
     * 
     * @param int $userId
     * @return array
     */
    public function getPendingInvites(int $userId): array
    {
        return $this->database->fetchAll(
            "SELECT p.*, e.title, e.description, e.start_date, e.end_date, 
                    e.location, u.name as creator_name
             FROM {prefix}calendar_event_participants p
             LEFT JOIN {prefix}calendar_events e ON p.event_id = e.id
             LEFT JOIN {prefix}users u ON e.user_id = u.id
             WHERE p.user_id = ? AND p.status = 'pending' 
               AND e.deleted_at IS NULL
               AND e.start_date > NOW()
             ORDER BY e.start_date ASC",
            [$userId]
        );
    }
    
    /**
     * Busca próximos eventos do usuário
     * 
     * @param int $userId
     * @param int $limit
     * @return array
     */
    public function getUpcomingEvents(int $userId, int $limit = 5): array
    {
        return $this->database->fetchAll(
            "SELECT e.*, u.name as creator_name
             FROM {prefix}calendar_events e
             LEFT JOIN {prefix}users u ON e.user_id = u.id
             WHERE e.deleted_at IS NULL
               AND e.start_date > NOW()
               AND (
                   e.user_id = ?
                   OR e.is_public = 1
                   OR EXISTS (
                       SELECT 1 FROM {prefix}calendar_event_participants p 
                       WHERE p.event_id = e.id AND p.user_id = ? AND p.status = 'accepted'
                   )
               )
             ORDER BY e.start_date ASC
             LIMIT ?",
            [$userId, $userId, $limit]
        );
    }
    
    /**
     * Valida dados do evento
     * 
     * @param array $data
     * @throws \Exception
     */
    private function validateEventData(array $data): void
    {
        if (empty($data['title'])) {
            throw new \Exception('Título do evento é obrigatório');
        }
        
        if (empty($data['start_date']) || empty($data['end_date'])) {
            throw new \Exception('Datas de início e fim são obrigatórias');
        }
        
        if (strtotime($data['start_date']) >= strtotime($data['end_date'])) {
            throw new \Exception('Data de início deve ser anterior à data de fim');
        }
        
        if (!empty($data['color']) && !array_key_exists($data['color'], self::COLORS)) {
            throw new \Exception('Cor inválida');
        }
        
        if (!empty($data['recurrence']) && !array_key_exists($data['recurrence'], self::RECURRENCE_TYPES)) {
            throw new \Exception('Tipo de recorrência inválido');
        }
    }
    
    /**
     * Determina cor do texto baseada na cor de fundo
     * 
     * @param string $backgroundColor
     * @return string
     */
    private function getTextColor(string $backgroundColor): string
    {
        // Remove # se presente
        $color = ltrim($backgroundColor, '#');
        
        // Converte para RGB
        $r = hexdec(substr($color, 0, 2));
        $g = hexdec(substr($color, 2, 2));
        $b = hexdec(substr($color, 4, 2));
        
        // Calcula luminância
        $luminance = (0.299 * $r + 0.587 * $g + 0.114 * $b) / 255;
        
        // Retorna branco para cores escuras, preto para cores claras
        return $luminance > 0.5 ? '#000000' : '#ffffff';
    }
}