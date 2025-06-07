<?php

namespace App\Policies;

use App\Models\CustomerFeedback;
use App\Models\Project; // TAMBAH INI - Missing import
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class CustomerFeedbackPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('List customer feedbacks');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, CustomerFeedback $customerFeedback): bool
    {
        return $user->can('View customer feedback') && (
            // Super Admin & Admin bisa lihat semua
            $user->hasRole(['Super Admin', 'Admin']) ||
            // Client hanya bisa lihat feedback mereka sendiri untuk project yang dia terlibat
            ($user->hasRole('Client') &&
             $customerFeedback->user_id === $user->id &&
             $this->userHasAccessToProject($user, $customerFeedback->project)) ||
            // Owner project bisa lihat feedback project mereka
            $customerFeedback->project->owner_id === $user->id ||
            // Member project bisa lihat feedback project mereka
            $customerFeedback->project->users()->where('users.id', $user->id)->exists()
        );
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('Create customer feedback');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, CustomerFeedback $customerFeedback): bool
    {
        return $user->can('Update customer feedback') && (
            // Super Admin & Admin bisa edit semua
            $user->hasRole(['Super Admin', 'Admin']) ||
            // Client hanya bisa edit feedback mereka sendiri yang masih pending untuk project yang dia terlibat
            ($user->hasRole('Client') &&
             $customerFeedback->user_id === $user->id &&
             $customerFeedback->status === 'pending' &&
             $this->userHasAccessToProject($user, $customerFeedback->project))
        );
    }

    /**
     * Helper method untuk cek apakah user punya akses ke project
     * PERBAIKAN: Ganti type hint Project menjadi $project saja
     */
    private function userHasAccessToProject(User $user, $project): bool
    {
        return $project->owner_id === $user->id ||
               $project->users()->where('users.id', $user->id)->exists();
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, CustomerFeedback $customerFeedback): bool
    {
        return $user->can('Delete customer feedback') &&
               $user->hasRole(['Super Admin', 'Admin']);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, CustomerFeedback $customerFeedback): bool
    {
        return $user->hasRole(['Super Admin', 'Admin']);
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, CustomerFeedback $customerFeedback): bool
    {
        return $user->hasRole(['Super Admin', 'Admin']);
    }
}
