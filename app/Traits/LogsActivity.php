<?php

namespace App\Traits;

use App\Models\Activity;
use Illuminate\Support\Facades\Auth;

trait LogsActivity
{
    /**
     * Enregistre une activité dans le journal.
     * @param string $action Nom de l'action (ex: 'quote_created')
     * @param array $data Données à enregistrer
     * @param \Illuminate\Database\Eloquent\Model|null $model Modèle affecté (optionnel)
     */
    protected function logActivity(string $action, array $data = [], $model = null): void
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();
        
        $activityData = [
            'user_id' => $user ? $user->id : null,
            'action' => $action,
            'data_snapshot' => $data,
            'ip_address' => request()->ip(),
        ];

        if ($model) {
            $activityData['model_type'] = $model::class;
            $activityData['model_id'] = $model->id;
        }

        try {
            Activity::create($activityData);
        } catch (\Exception $e) {
            // Log d'erreur si l'enregistrement de l'activité échoue
            logger()->error("Failed to log activity: " . $e->getMessage(), $activityData);
        }
    }
}