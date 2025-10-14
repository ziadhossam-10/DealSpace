<?php

namespace App\Providers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;

class QueryTrackerProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot()
    {
        if (config('app.debug')) {
            DB::listen(function ($query) {
                // Get the actual values
                $sql = $query->sql;
                $bindings = $query->bindings;
                $time = $query->time;

                // Interpolate bindings into SQL
                $fullQuery = $this->interpolateQuery($sql, $bindings);

                // SET BREAKPOINT HERE - you'll see the full query with values
                $queryInfo = [
                    'original' => $sql,
                    'bindings' => $bindings,
                    'full_query' => $fullQuery, // This shows actual values
                    'time' => $time
                ];
            });
        }
    }

    private function interpolateQuery($sql, $bindings)
    {
        foreach ($bindings as $binding) {
            if (is_string($binding)) {
                $value = "'" . addslashes($binding) . "'";
            } elseif (is_null($binding)) {
                $value = 'NULL';
            } elseif (is_bool($binding)) {
                $value = $binding ? 'TRUE' : 'FALSE';
            } else {
                $value = $binding;
            }

            $sql = preg_replace('/\?/', $value, $sql, 1);
        }

        return $sql;
    }
}
