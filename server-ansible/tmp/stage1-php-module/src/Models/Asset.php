<?php

namespace SnipeIT\Module\Models;

use SnipeIT\Module\Services\Database;

/**
 * Asset model — represents a physical hardware item.
 *
 * Fields mirror the Snipe-IT `assets` table.
 * All persistence uses PDO via Database service (no Eloquent).
 */
class Asset
{
    public ?int    $id           = null;
    public ?string $name         = null;
    public ?string $asset_tag    = null;
    public ?int    $model_id     = null;
    public ?string $serial       = null;
    public ?string $purchase_date = null;
    public ?float  $purchase_cost = null;
    public ?string $order_number  = null;
    public ?int    $assigned_to   = null;
    public ?string $assigned_type = null;
    public ?string $notes         = null;
    public ?int    $user_id       = null;
    public ?int    $status_id     = null;
    public ?int    $company_id    = null;
    public ?int    $location_id   = null;
    public ?int    $supplier_id   = null;
    public ?string $warranty_months = null;
    public ?string $created_at   = null;
    public ?string $updated_at   = null;
    public ?string $deleted_at   = null;

    public static function find(int $id): ?self
    {
        $row = Database::fetchOne(
            'SELECT * FROM assets WHERE id = ? AND deleted_at IS NULL',
            [$id]
        );
        return $row ? self::fromRow($row) : null;
    }

    /** @return self[] */
    public static function all(int $limit = 100, int $offset = 0): array
    {
        $rows = Database::fetchAll(
            'SELECT * FROM assets WHERE deleted_at IS NULL ORDER BY id DESC LIMIT ? OFFSET ?',
            [$limit, $offset]
        );
        return array_map([self::class, 'fromRow'], $rows);
    }

    /** @return self[] */
    public static function search(string $query, int $limit = 50): array
    {
        $like = '%' . $query . '%';
        $rows = Database::fetchAll(
            'SELECT * FROM assets WHERE deleted_at IS NULL
             AND (name LIKE ? OR asset_tag LIKE ? OR serial LIKE ?)
             ORDER BY id DESC LIMIT ?',
            [$like, $like, $like, $limit]
        );
        return array_map([self::class, 'fromRow'], $rows);
    }

    public function save(): bool
    {
        $this->updated_at = date('Y-m-d H:i:s');

        if ($this->id === null) {
            $this->created_at = date('Y-m-d H:i:s');
            $id = Database::insert(
                'INSERT INTO assets
                 (name, asset_tag, model_id, serial, purchase_date, purchase_cost,
                  order_number, assigned_to, assigned_type, notes, user_id,
                  status_id, company_id, location_id, supplier_id, warranty_months,
                  created_at, updated_at)
                 VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)',
                [
                    $this->name, $this->asset_tag, $this->model_id, $this->serial,
                    $this->purchase_date, $this->purchase_cost, $this->order_number,
                    $this->assigned_to, $this->assigned_type, $this->notes,
                    $this->user_id, $this->status_id, $this->company_id,
                    $this->location_id, $this->supplier_id, $this->warranty_months,
                    $this->created_at, $this->updated_at,
                ]
            );
            $this->id = (int) $id;
            return $this->id > 0;
        }

        $affected = Database::execute(
            'UPDATE assets SET
             name=?, asset_tag=?, model_id=?, serial=?, purchase_date=?,
             purchase_cost=?, order_number=?, assigned_to=?, assigned_type=?,
             notes=?, status_id=?, company_id=?, location_id=?, supplier_id=?,
             warranty_months=?, updated_at=?
             WHERE id=?',
            [
                $this->name, $this->asset_tag, $this->model_id, $this->serial,
                $this->purchase_date, $this->purchase_cost, $this->order_number,
                $this->assigned_to, $this->assigned_type, $this->notes,
                $this->status_id, $this->company_id, $this->location_id,
                $this->supplier_id, $this->warranty_months, $this->updated_at,
                $this->id,
            ]
        );
        return $affected >= 0;
    }

    /** Soft-delete */
    public function delete(): bool
    {
        if ($this->id === null) {
            return false;
        }
        Database::execute(
            'UPDATE assets SET deleted_at = ? WHERE id = ?',
            [date('Y-m-d H:i:s'), $this->id]
        );
        $this->deleted_at = date('Y-m-d H:i:s');
        return true;
    }

    /** Checkout asset to a user */
    public function checkoutToUser(int $userId): bool
    {
        $this->assigned_to   = $userId;
        $this->assigned_type = 'user';
        return $this->save();
    }

    /** Check asset back in */
    public function checkin(): bool
    {
        $this->assigned_to   = null;
        $this->assigned_type = null;
        return $this->save();
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
