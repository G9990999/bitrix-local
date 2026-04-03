<?php

namespace SnipeIT\Module\Models;

use SnipeIT\Module\Services\Database;

/**
 * User model — person responsible for or assigned an asset.
 */
class User
{
    public ?int    $id          = null;
    public ?string $first_name  = null;
    public ?string $last_name   = null;
    public ?string $username    = null;
    public ?string $email       = null;
    public ?string $password    = null;
    public ?int    $company_id  = null;
    public ?int    $location_id = null;
    public ?int    $department_id = null;
    public ?string $employee_num = null;
    public ?string $jobtitle    = null;
    public ?string $phone       = null;
    public ?string $created_at  = null;
    public ?string $updated_at  = null;
    public ?string $deleted_at  = null;

    public function fullName(): string
    {
        return trim($this->first_name . ' ' . $this->last_name);
    }

    public static function find(int $id): ?self
    {
        $row = Database::fetchOne(
            'SELECT * FROM users WHERE id = ? AND deleted_at IS NULL',
            [$id]
        );
        return $row ? self::fromRow($row) : null;
    }

    public static function findByUsername(string $username): ?self
    {
        $row = Database::fetchOne(
            'SELECT * FROM users WHERE username = ? AND deleted_at IS NULL',
            [$username]
        );
        return $row ? self::fromRow($row) : null;
    }

    /** @return self[] */
    public static function all(int $limit = 100, int $offset = 0): array
    {
        $rows = Database::fetchAll(
            'SELECT * FROM users WHERE deleted_at IS NULL ORDER BY last_name, first_name LIMIT ? OFFSET ?',
            [$limit, $offset]
        );
        return array_map([self::class, 'fromRow'], $rows);
    }

    /** @return Asset[] Assets currently assigned to this user */
    public function assignedAssets(): array
    {
        if ($this->id === null) {
            return [];
        }
        $rows = Database::fetchAll(
            "SELECT * FROM assets WHERE assigned_to = ? AND assigned_type = 'user' AND deleted_at IS NULL",
            [$this->id]
        );
        return array_map([Asset::class, 'fromRow'], $rows);
    }

    public function save(): bool
    {
        $this->updated_at = date('Y-m-d H:i:s');

        if ($this->id === null) {
            $this->created_at = date('Y-m-d H:i:s');
            $id = Database::insert(
                'INSERT INTO users
                 (first_name, last_name, username, email, password,
                  company_id, location_id, department_id, employee_num, jobtitle, phone,
                  created_at, updated_at)
                 VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)',
                [
                    $this->first_name, $this->last_name, $this->username,
                    $this->email, $this->password,
                    $this->company_id, $this->location_id, $this->department_id,
                    $this->employee_num, $this->jobtitle, $this->phone,
                    $this->created_at, $this->updated_at,
                ]
            );
            $this->id = (int) $id;
            return $this->id > 0;
        }

        Database::execute(
            'UPDATE users SET first_name=?, last_name=?, username=?, email=?,
             company_id=?, location_id=?, department_id=?, employee_num=?,
             jobtitle=?, phone=?, updated_at=? WHERE id=?',
            [
                $this->first_name, $this->last_name, $this->username, $this->email,
                $this->company_id, $this->location_id, $this->department_id,
                $this->employee_num, $this->jobtitle, $this->phone,
                $this->updated_at, $this->id,
            ]
        );
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
