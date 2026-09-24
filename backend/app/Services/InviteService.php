<?php

namespace App\Services;

use App\Enums\InviteStatus;
use App\Mail\InvitationMail;
use App\Models\Invite;
use App\Models\User;
use App\Repositories\InviteRepository;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class InviteService
{
    public function __construct(private InviteRepository $invitesRepository) {}

    /** @param array{email: string, role?: string} $data */
    public function create(User $actor, array $data): Invite
    {
        try {
            $invite = DB::transaction(function () use ($actor, $data): Invite {
                $email = $data['email'];

                if ($this->invitesRepository->accountExists($email)) {
                    throw ValidationException::withMessages([
                        'email' => ['Este usuário já possui uma conta.'],
                    ]);
                }

                $pending = $this->invitesRepository->pendingForEmail($email);

                if ($pending !== null) {
                    if ($pending->expires_at->isFuture()) {
                        throw ValidationException::withMessages([
                            'email' => ['Já existe um convite pendente para este e-mail.'],
                        ]);
                    }

                    $this->invitesRepository->expire($pending);
                }

                $token = Str::random(64);

                return $this->invitesRepository->create([
                    'email' => $email,
                    'tenancy_id' => $actor->tenancy_id,
                    'user_id' => $actor->id,
                    'role' => $data['role'] ?? 'employee',
                    'status' => InviteStatus::Pending,
                    'token' => $token,
                    'invite_url' => rtrim(config('app.frontend_url'), '/').'/invite/'.$token,
                    'expires_at' => now()->addDays(7),
                ]);
            });
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages([
                'email' => ['Já existe um convite pendente para este e-mail.'],
            ]);
        }

        Mail::to($invite->email)->queue((new InvitationMail(
            companyName: $invite->tenancy->name,
            inviteUrl: $invite->invite_url,
            expiresAt: $invite->expires_at,
        ))->onQueue('emails'));

        return $invite;
    }

    public function findValid(string $token): Invite
    {
        $invite = $this->invitesRepository->findByTokenOrFail($token);
        $this->assertValid($invite);

        return $invite;
    }

    /** @param array{name: string, password: string} $data */
    public function accept(string $token, array $data): User
    {
        return DB::transaction(function () use ($token, $data): User {
            $invite = $this->invitesRepository->findByTokenOrFail($token, lock: true);
            $this->assertValid($invite);

            if ($this->invitesRepository->accountExists($invite->email)) {
                throw ValidationException::withMessages([
                    'email' => ['Este usuário já possui uma conta.'],
                ]);
            }

            $user = new User;
            $user->tenancy_id = $invite->tenancy_id;
            $user->name = $data['name'];
            $user->email = $invite->email;
            $user->password = $data['password'];
            $user->role = $invite->role;
            $user->save();

            $this->invitesRepository->accept($invite);

            return $user;
        });
    }

    private function assertValid(Invite $invite): void
    {
        if ($invite->status !== InviteStatus::Pending || ! $invite->expires_at->isFuture()) {
            throw ValidationException::withMessages([
                'token' => ['Este convite não está mais válido.'],
            ]);
        }
    }
}
