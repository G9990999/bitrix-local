<?php

namespace SnipeIT\Module\Models;

use SnipeIT\Module\Services\Database;

/**
 * License model — software license with seat tracking.
 */
class License
{
    public ?int    $id              = null;
    public ?string $name            = null;
    public ?string $serial          = null;
    public ?int    $seats           = null;
    public ?float  $purchase_cost   = null;
    public ?string $purchase_date   = null;
    public ?string $expiration_date = null;
    public ?int    $manufacturer_id = null;
    public ?int    $supplier_id     = null;
    public ?int    $category_id     = null;
    public ?int    $company_id      = null;
    public ?string $notes           = null;
    public ?int    $user_id         = null;
    public ?string $created_at      = null;
    public ?string $updated_at      = null;
    public ?string $deleted_at      = null;

    public static function find(int $id): ?self
    {
        $row = Database::fetchOne(
            'SELECT * FROM licenses WHERE id = ? AND deleted_at IS NULL',
            [$id]
        );
        return $row ? self::fromRow($row) : null;
    }

    /** @return self[] */
    public static function all(int $limit = 100, int $offset = 0): array
    {
        $rows = Database::fetchAll(
            'SELECT * FROM licenses WHERE deleted_at IS NULL ORDER BY name LIMIT ? OFFSET ?',
            [$limit, $offset]
        );
        return array_map([self::class, 'fromRow'], $rows);
    }

    /** Count available (unassigned) seats */
    public function availableSeats(): int
    {
        if ($this->id === null || $this->seats === null) {
            return 0;
        }
        $used = Database::fetchOne(
            'SELECT COUNT(*) AS cnt FROM license_seats WHERE license_id = ? AND assigned_to IS NOT NULL AND deleted_at IS NULL',
            [$this->id]
        );
        return $this->seats - (int) ($used['cnt'] ?? 0);
    }

    public function save(): bool
    {
        $this->updated_at = date('Y-m-d H:i:s');

        if ($this->id === null) {
            $this->created_at = date('Y-m-d H:i:s');
            $id = Database::insert(
                'INSERT INTO licenses
                 (name, serial, seats, purchase_cost, purchase_date, expiration_date,
                  manufacturer_id, supplier_id, category_id, company_id, notes, user_id,
                  created_at, updated_at)
                 VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)',
                [
                    $this->name, $this->serial, $this->seats,
                    $this->purchase_cost, $this->purchase_date, $this->expiration_date,
                    $this->manufacturer_id, $this->supplier_id, $this->category_id,
                    $this->company_id, $this->notes, $this->user_id,
                    $this->created_at, $this->updated_at,
                ]
            );
            $this->id = (int) $id;
            return $this->id > 0;
        }

        Database::execute(
            'UPDATE licenses SET name=?, serial=?, seats=?, purchase_cost=?,
             purchase_date=?, expiration_date=?, manufacturer_id=?, supplier_id=?,
             category_id=?, company_id=?, notes=?, updated_at=? WHERE id=?',
            [
                $this->name, $this->serial, $this->seats, $this->purchase_cost,
                $this->purchase_date, $this->expiration_date, $this->manufacturer_id,
                $this->supplier_id, $this->category_id, $this->company_id,
                $this->notes, $this->updated_at, $this->id,
            ]
        );
        return true;
    }

    public function delete(): bool
    {
        if ($this->id === null) {
            return false;
        }
        Database::execute(
            'UPDATE licenses SET deleted_at = ? WHERE id = ?',
            [date('Y-m-d H:i:s'), $this->id]
        );
        $this->deleted_at = date('Y-m-d H:i:s');
        return true;
    }

    private static function fromRow(array $row): self
    {
        $obj = new self();
        foreach ($row as $key => $value) {
            if (property_exists($obj, $key)) {
                $obj->$key = $value;
            }
        }
        return $obj;
    }
}
