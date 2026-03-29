<?php

namespace Modules\VinManagement\Repositories;

use Modules\VinManagement\Entities\VinNumber;

class VinNumberRepository
{
    public function formatRow(array $row): array
    {
        return [
            'car_brand' => $row['car_brand'] ?? null,
            'car_model' => $row['car_model'] ?? null,
            'color' => $row['color'] ?? null,
            'vin_number' => $row['vin_number'] ?? null,
            'year' => isset($row['year']) ? (int) $row['year'] : null,
            'manufacturer' => $row['manufacturer'] ?? null,
            'car_type' => $row['car_type'] ?? null,
            'transmission' => $row['transmission'] ?? null,
        ];
    }

    public function bulkStore(array $rows): void
    {
        foreach ($rows as $row) {
            $data = $this->formatRow($row);
            if (!empty($data['vin_number'])) {
                VinNumber::updateOrCreate(
                    ['vin_number' => $data['vin_number']],
                    $data
                );
            }
        }
    }

    /**
     * Only add new records; do not overwrite existing data.
     */
    public function bulkStoreAddOnly(array $rows): void
    {
        foreach ($rows as $row) {
            $data = $this->formatRow($row);
            if (empty($data['vin_number'])) { continue; }
            $exists = VinNumber::where('vin_number', $data['vin_number'])->exists();
            if (!$exists) {
                VinNumber::create($data);
            }
        }
    }
}