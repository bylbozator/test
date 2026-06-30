<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;

class FetchApiData extends Command
{
    protected $signature = 'app:fetch-api-data {entity?}';

    protected $description = 'Fetch data from WB API and save to database';

    private const LIMIT = 500;

    private array $entities = [
        'sales' => '/api/sales',
        'orders' => '/api/orders',
        'stocks' => '/api/stocks',
        'incomes' => '/api/incomes',
    ];

    public function handle()
    {
        $entities = $this->argument('entity')
            ? [$this->argument('entity')]
            : array_keys($this->entities);

        foreach ($entities as $entity) {
            if (!isset($this->entities[$entity])) {
                $this->error("Unknown entity: $entity");
                continue;
            }
            $this->info("Fetching $entity...");
            $this->fetchEntity($entity);
        }

        $this->info('Done!');
    }

    private function fetchEntity(string $entity): void
    {
        $endpoint = $this->entities[$entity];
        $page = 1;

        do {
            $params = [
                'limit' => self::LIMIT,
                'page' => $page,
                'key' => config('app.api_key'),
            ];

            if ($entity === 'stocks') {
                $params['dateFrom'] = now()->toDateString();
            } else {
                $params['dateFrom'] = now()->subMonth()->toDateString();
                $params['dateTo'] = now()->toDateString();
            }

            $response = Http::get(config('app.api_base_url') . $endpoint, $params);

            if ($response->failed()) {
                $this->error("Failed to fetch $entity page $page: " . $response->status());
                break;
            }

            $data = $response->json();

            if (empty($data['data'])) {
                break;
            }

            $records = $data['data'];

            DB::table($entity)->upsert(
                $records,
                $this->getUniqueColumns($entity),
                $this->getUpdateColumns($entity)
            );

            $this->info("  Page $page: " . count($records) . " records saved");

            $lastPage = $data['meta']['last_page'] ?? 1;
            $page++;

        } while ($page <= $lastPage);

        $this->info("  Total: {$this->getEntityCount($entity)} records in DB");
    }

    private function getUniqueColumns(string $entity): array
    {
        return match ($entity) {
            'sales' => ['sale_id'],
            'orders' => ['g_number', 'nm_id'],
            'stocks' => ['date', 'nm_id', 'warehouse_name'],
            'incomes' => ['date', 'nm_id', 'barcode'],
            default => ['id'],
        };
    }

    private function getUpdateColumns(string $entity): array
    {
        return match ($entity) {
            'sales' => ['date', 'last_change_date', 'supplier_article', 'tech_size', 'barcode', 'total_price', 'discount_percent', 'is_supply', 'is_realization', 'promo_code_discount', 'warehouse_name', 'country_name', 'oblast_okrug_name', 'region_name', 'income_id', 'odid', 'spp', 'for_pay', 'finished_price', 'price_with_disc', 'nm_id', 'subject', 'category', 'brand', 'is_storno'],
            'orders' => ['date', 'last_change_date', 'supplier_article', 'tech_size', 'barcode', 'total_price', 'discount_percent', 'warehouse_name', 'oblast', 'income_id', 'odid', 'nm_id', 'subject', 'category', 'brand', 'is_cancel', 'cancel_dt'],
            'stocks' => ['last_change_date', 'supplier_article', 'tech_size', 'barcode', 'quantity', 'is_supply', 'is_realization', 'quantity_full', 'in_way_to_client', 'in_way_from_client', 'subject', 'category', 'brand', 'sc_code', 'price', 'discount'],
            'incomes' => ['last_change_date', 'supplier_article', 'tech_size', 'barcode', 'quantity', 'total_price', 'date_close', 'warehouse_name', 'subject', 'category', 'brand'],
            default => [],
        };
    }

    private function getEntityCount(string $entity): int
    {
        return DB::table($entity)->count();
    }
}
