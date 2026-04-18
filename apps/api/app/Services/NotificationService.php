<?php

namespace App\Services;

use App\Models\School;
use App\Models\User;

class NotificationService
{
    /**
     * @param  array<string, mixed>  $data
     * @param  list<string>  $channels
     */
    public function send(User $recipient, School $school, string $type, array $data, array $channels = ['in_app']): void
    {
        if (! in_array('in_app', $channels, true)) {
            return;
        }

        $school->notifications()->create([
            'recipient_user_id' => $recipient->id,
            'type' => $type,
            'title' => $data['title'] ?? str($type)->replace('.', ' ')->headline()->toString(),
            'body' => $data['body'] ?? '',
            'data' => $data,
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function sendToSchoolMembers(School $school, string $type, array $data): void
    {
        $school->memberships()
            ->where('status', 'active')
            ->with('user')
            ->get()
            ->each(function ($membership) use ($school, $type, $data): void {
                if ($membership->user) {
                    $this->send($membership->user, $school, $type, $data);
                }
            });
    }
}
