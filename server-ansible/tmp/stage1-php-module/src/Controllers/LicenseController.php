<?php

namespace SnipeIT\Module\Controllers;

use SnipeIT\Module\Models\License;

/**
 * License controller — CRUD for software licenses.
 */
class LicenseController extends BaseController
{
    /** GET /licenses */
    public function index(): void
    {
        $page   = max(1, (int) ($_GET['page'] ?? 1));
        $limit  = 25;
        $offset = ($page - 1) * $limit;

        $licenses = License::all($limit, $offset);
        $this->render('licenses/index', compact('licenses', 'page'));
    }

    /** GET /licenses/{id} */
    public function show(int $id): void
    {
        $license = License::find($id);
        if ($license === null) {
            $this->notFound('License not found');
            return;
        }
        $availableSeats = $license->availableSeats();
        $this->render('licenses/show', compact('license', 'availableSeats'));
    }

    /** GET /licenses/create */
    public function create(): void
    {
        $this->render('licenses/create', ['license' => new License()]);
    }

    /** POST /licenses */
    public function store(): void
    {
        $license = $this->fillFromPost(new License());
        if ($license->save()) {
            $this->redirect('/licenses/' . $license->id);
        } else {
            $this->render('licenses/create', ['license' => $license, 'error' => 'Save failed']);
        }
    }

    /** POST /licenses/{id}/delete */
    public function destroy(int $id): void
    {
        $license = License::find($id);
        if ($license !== null) {
            $license->delete();
        }
        $this->redirect('/licenses');
    }

    private function fillFromPost(License $license): License
    {
        $fields = ['name', 'serial', 'purchase_date', 'expiration_date', 'notes'];
        foreach ($fields as $field) {
            if (isset($_POST[$field])) {
                $license->$field = $_POST[$field] !== '' ? $_POST[$field] : null;
            }
        }
        $intFields = ['seats', 'manufacturer_id', 'supplier_id', 'category_id', 'company_id'];
        foreach ($intFields as $field) {
            if (isset($_POST[$field])) {
                $license->$field = $_POST[$field] !== '' ? (int) $_POST[$field] : null;
            }
        }
        if (isset($_POST['purchase_cost'])) {
            $license->purchase_cost = $_POST['purchase_cost'] !== '' ? (float) $_POST['purchase_cost'] : null;
        }
        return $license;
    }
}
