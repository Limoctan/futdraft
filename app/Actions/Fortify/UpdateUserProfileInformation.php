<?php

namespace App\Actions\Fortify;

use App\Concerns\ProfileValidationRules;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Laravel\Fortify\Contracts\UpdatesUserProfileInformation;

class UpdateUserProfileInformation implements UpdatesUserProfileInformation
{
    use ProfileValidationRules;

    /**
     * Validate and update the user's profile information.
     *
     * @param  array<string, mixed>  $input
     */
    public function update(User $user, array $input): void
    {
        Validator::make($input, [
            'name' => $this->nameRules(),
            'username' => $this->usernameRules($user->id),
            'email' => $this->emailRules($user->id),
        ])->validateWithBag('updateProfileInformation');

        if (
            $input['email'] !== $user->email ||
            $input['username'] !== $user->username
        ) {
            $user->forceFill([
                'name' => $input['name'],
                'username' => $input['username'],
                'email' => $input['email'],
                'email_verified_at' => null,
            ])->save();
        } else {
            $user->forceFill([
                'name' => $input['name'],
                'username' => $input['username'],
                'email' => $input['email'],
            ])->save();
        }
    }
}
