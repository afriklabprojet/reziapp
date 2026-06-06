<?php

namespace App\Services;

use App\Models\Campaign;
use App\Models\CampaignSend;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class CampaignService
{
    public function getCampaignRecipients(Campaign $campaign): Collection
    {
        $query = User::query();

        switch ($campaign->audience) {
            case 'owners':
                $query->where('role', 'owner');
                break;
            case 'clients':
                $query->where('role', 'user');
                break;
            case 'inactive_users':
                $query->where('last_login_at', '<', now()->subDays(30));
                break;
            case 'new_users':
                $query->where('created_at', '>=', now()->subDays(7));
                break;
            case 'high_value':
                $query->has('sentContacts', '>=', 5);
                break;
            case 'custom':
                if ($campaign->audience_filters) {
                    $this->applyAudienceFilters($query, $campaign->audience_filters);
                }
                break;
            default:
                break;
        }

        if ($campaign->excluded_user_ids) {
            $query->whereNotIn('id', $campaign->excluded_user_ids);
        }

        return $query->get();
    }

    public function createCampaign(array $data): Campaign
    {
        return Campaign::create([
            'user_id' => $data['user_id'],
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'type' => $data['type'] ?? 'email',
            'subject' => $data['subject'] ?? null,
            'content' => $data['content'],
            'template' => $data['template'] ?? null,
            'audience' => $data['audience'] ?? 'all_users',
            'audience_filters' => $data['audience_filters'] ?? null,
            'scheduled_at' => $data['scheduled_at'] ?? null,
            'status' => $data['status'] ?? 'draft',
        ]);
    }

    public function sendCampaign(Campaign $campaign): array
    {
        if ($campaign->status === 'sent') {
            return ['success' => false, 'error' => 'Cette campagne a déjà été envoyée'];
        }

        $recipients = $this->getCampaignRecipients($campaign);
        $sent = 0;
        $failed = 0;

        foreach ($recipients as $user) {
            try {
                $this->sendCampaignToUser($campaign, $user);
                $sent++;
            } catch (Throwable $exception) {
                $failed++;
                Log::error("Failed to send campaign {$campaign->id} to user {$user->id}: ".$exception->getMessage());
            }
        }

        $campaign->update([
            'status' => 'sent',
            'sent_at' => now(),
            'recipients_count' => $sent,
        ]);

        return [
            'success' => true,
            'sent' => $sent,
            'failed' => $failed,
            'total' => $recipients->count(),
        ];
    }

    protected function applyAudienceFilters(Builder $query, array $filters): void
    {
        if (isset($filters['commune'])) {
            $query->whereHas('residences', function (Builder $builder) use ($filters): void {
                $builder->where('commune', $filters['commune']);
            });
        }

        if (isset($filters['min_residences'])) {
            $query->has('residences', '>=', $filters['min_residences']);
        }

        if (isset($filters['registered_after'])) {
            $query->where('created_at', '>=', $filters['registered_after']);
        }

        if (isset($filters['registered_before'])) {
            $query->where('created_at', '<=', $filters['registered_before']);
        }
    }

    protected function sendCampaignToUser(Campaign $campaign, User $user): CampaignSend
    {
        $content = $this->personalizeCampaignContent($campaign->content, $user);

        $send = CampaignSend::create([
            'campaign_id' => $campaign->id,
            'user_id' => $user->id,
            'status' => 'pending',
        ]);

        try {
            switch ($campaign->type) {
                case 'email':
                    $this->sendCampaignEmail($campaign, $user, $content);
                    break;
                case 'sms':
                    $this->sendCampaignSms($user, $content);
                    break;
                case 'push':
                case 'in_app':
                    $this->sendCampaignNotification($user, $campaign->subject ?? $campaign->name, $content);
                    break;
                default:
                    throw new \InvalidArgumentException('Type de campagne non supporté.');
            }

            $send->update([
                'status' => 'sent',
                'sent_at' => now(),
            ]);
        } catch (Throwable $exception) {
            $send->update([
                'status' => 'failed',
                'error_message' => $exception->getMessage(),
            ]);

            throw $exception;
        }

        return $send;
    }

    protected function personalizeCampaignContent(string $content, User $user): string
    {
        $replacements = [
            '{{name}}' => $user->name,
            '{{first_name}}' => explode(' ', $user->name)[0],
            '{{email}}' => $user->email,
            '{{phone}}' => $user->phone ?? '',
            '{{referral_code}}' => $user->referral_code ?? '',
        ];

        return str_replace(array_keys($replacements), array_values($replacements), $content);
    }

    protected function sendCampaignEmail(Campaign $campaign, User $user, string $content): void
    {
        Mail::send([], [], function ($message) use ($campaign, $user, $content): void {
            $message->to($user->email, $user->name)
                ->subject($campaign->subject ?? $campaign->name)
                ->html($content);
        });
    }

    protected function sendCampaignSms(User $user, string $content): void
    {
        if (! $user->phone) {
            return;
        }

        try {
            app(SmsService::class)->send($user->phone, $content);
        } catch (Throwable $exception) {
            Log::warning('Failed to send campaign SMS', [
                'user_id' => $user->id,
                'error' => $exception->getMessage(),
            ]);
        }
    }

    protected function sendCampaignNotification(User $user, string $title, string $content): void
    {
        \App\Models\Notification::send($user, 'campaign', $title, $content);
    }
}
