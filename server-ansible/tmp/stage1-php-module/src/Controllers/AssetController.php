<?php

namespace SnipeIT\Module\Controllers;

use SnipeIT\Module\Models\Asset;

/**
 * Asset controller — handles CRUD + checkout/checkin HTTP actions.
 * No framework dependency: reads from $_GET/$_POST, renders views directly.
 */
class AssetController extends BaseController
{
    /** GET /assets */
    public function index(): void
    {
        $search = $_GET['search'] ?? '';
        $page   = max(1, (int) ($_GET['page'] ?? 1));
        $limit  = 25;
        $offset = ($page - 1) * $limit;

        $assets = $search
            ? Asset::search($search, $limit)
            : Asset::all($limit, $offset);

        $this->render('assets/index', compact('assets', 'search', 'page'));
    }

    /** GET /assets/{id} */
    public function show(int $id): void
    {
        $asset = Asset::find($id);
        if ($asset === null) {
            $this->notFound('Asset not found');
            return;
        }
        $this->render('assets/show', compact('asset'));
    }

    /** GET /assets/create */
    public function create(): void
    {
        $this->render('assets/create', ['asset' => new Asset()]);
    }

    /** POST /assets */
    public function store(): void
    {
        $asset = $this->fillFromPost(new Asset());
        if ($asset->save()) {
            $this->redirect('/assets/' . $asset->id);
        } else {
            $this->render('assets/create', ['asset' => $asset, 'error' => 'Save failed']);
        }
    }

    /** GET /assets/{id}/edit */
    public function edit(int $id): void
    {
        $asset = Asset::find($id);
        if ($asset === null) {
            $this->notFound('Asset not found');
            return;
        }
        $this->render('assets/edit', compact('asset'));
    }

    /** POST /assets/{id}/update */
    public function update(int $id): void
    {
        $asset = Asset::find($id);
        if ($asset === null) {
            $this->notFound('Asset not found');
            return;
        }
        $asset = $this->fillFromPost($asset);
        $asset->save();
        $this->redirect('/assets/' . $asset->id);
    }

    /** POST /assets/{id}/delete */
    public function destroy(int $id): void
    {
        $asset = Asset::find($id);
        if ($asset !== null) {
            $asset->delete();
        }
        $this->redirect('/assets');
    }

    /** POST /assets/{id}/checkout */
    public function checkout(int $id): void
    {
        $asset  = Asset::find($id);
        $userId = (int) ($_POST['assigned_to'] ?? 0);

        if ($asset !== null && $userId > 0) {
            $asset->checkoutToUser($userId);
        }
        $this->redirect('/assets/' . $id);
    }

    /** POST /assets/{id}/checkin */
    public function checkin(int $id): void
    {
        $asset = Asset::find($id);
        if ($asset !== null) {
            $asset->checkin();
        }
        $this->redirect('/assets/' . $id);
    }

    private function fillFromPost(Asset $asset): Asset
    {
        $fields = [
            'name', 'asset_tag', 'serial', 'purchase_date', 'purchase_cost',
            'order_number', 'notes', 'warranty_months',
        ];
        foreach ($fields as $field) {
            if (isset($_POST[$field])) {
                $asset->$field = $_POST[$field] !== '' ? $_POST[$field] : null;
            }
        }
        $intFields = ['model_id', 'status_id', 'company_id', 'location_id', 'supplier_id'];
        foreach ($intFields as $field) {
            if (isset($_POST[$field])) {
                $asset->$field = $_POST[$field] !== '' ? (int) $_POST[$field] : null;
            }
        }
        return $asset;
    }
}
