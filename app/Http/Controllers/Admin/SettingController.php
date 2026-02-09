<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\PaymentMethod;
use App\Models\Setting;
use App\Models\Shop;
use Illuminate\Http\Request;

/**
 * Paramétrage du système - Admin uniquement
 * Supporte les paramètres globaux et par boutique
 */
class SettingController extends Controller
{
    public function index(Request $request)
    {
        $shops = Shop::active()->orderBy('name')->get();
        $selectedShopId = $request->get('shop_id');
        $selectedShop = $selectedShopId ? Shop::find($selectedShopId) : null;
        
        // Récupérer les paramètres selon le contexte
        if ($selectedShopId) {
            // Paramètres pour une boutique spécifique
            // D'abord les globaux, puis on override avec ceux de la boutique
            $globalSettings = Setting::whereNull('shop_id')->pluck('value', 'key')->toArray();
            $shopSettings = Setting::where('shop_id', $selectedShopId)->pluck('value', 'key')->toArray();
            $settings = array_merge($globalSettings, $shopSettings);
        } else {
            // Paramètres globaux uniquement
            $settings = Setting::whereNull('shop_id')->pluck('value', 'key')->toArray();
        }
        
        $paymentMethods = PaymentMethod::ordered()->get();

        return view('admin.settings.index', compact('settings', 'paymentMethods', 'shops', 'selectedShopId', 'selectedShop'));
    }

    public function update(Request $request)
    {
        $shopId = $request->shop_id;
        
        // Liste des clés de paramètres attendues
        $settingKeys = [
            'company_name', 'company_phone', 'company_email', 'company_website',
            'company_address', 'company_siret', 'company_tva',
            'invoice_prefix', 'quote_prefix', 'repair_prefix', 'default_tva_rate', 'invoice_footer',
            'repair_warranty_days', 'repair_default_diagnostic_fee', 'repair_terms',
            'notify_low_stock', 'low_stock_threshold', 'notify_repair_ready', 'send_sms_notifications',
            'receipt_printer_name', 'receipt_width', 'auto_print_receipt', 'print_logo',
        ];
        
        // Valeurs des checkboxes (si non cochées, elles ne sont pas envoyées)
        $checkboxKeys = ['notify_low_stock', 'notify_repair_ready', 'send_sms_notifications', 'auto_print_receipt', 'print_logo'];

        foreach ($settingKeys as $key) {
            $value = $request->input($key);
            
            // Pour les checkboxes, mettre 0 si non présentes
            if (in_array($key, $checkboxKeys)) {
                $value = $request->has($key) ? '1' : '0';
            }
            
            // Si la valeur est vide et on est sur une boutique, on ne sauvegarde pas (utilise global)
            if ($shopId && ($value === null || $value === '')) {
                // Supprimer le paramètre de la boutique si existant
                Setting::where('key', $key)->where('shop_id', $shopId)->delete();
                continue;
            }
            
            if ($shopId) {
                // Paramètre spécifique à une boutique
                $existing = Setting::where('key', $key)->where('shop_id', $shopId)->first();
                
                if ($existing) {
                    $existing->update(['value' => $value]);
                } else {
                    // Récupérer info du paramètre global
                    $global = Setting::where('key', $key)->whereNull('shop_id')->first();
                    
                    Setting::create([
                        'key' => $key,
                        'value' => $value,
                        'type' => $global->type ?? 'string',
                        'group' => $global->group ?? 'general',
                        'description' => $global->description ?? null,
                        'shop_id' => $shopId,
                        'is_global' => false,
                    ]);
                }
            } else {
                // Paramètre global
                Setting::updateOrCreate(
                    ['key' => $key, 'shop_id' => null],
                    ['value' => $value, 'is_global' => true]
                );
            }
        }

        $shopName = $shopId ? Shop::find($shopId)->name : 'Global';
        ActivityLog::log('update', null, null, null, "Mise à jour des paramètres: {$shopName}");

        $redirectUrl = route('admin.settings.index');
        if ($shopId) {
            $redirectUrl .= '?shop_id=' . $shopId;
        }

        return redirect($redirectUrl)->with('success', 'Paramètres mis à jour avec succès.');
    }

    /**
     * Réinitialiser les paramètres d'une boutique aux valeurs globales
     */
    public function resetToGlobal($shopId)
    {
        $shop = Shop::findOrFail($shopId);
        
        // Supprimer tous les paramètres spécifiques à cette boutique
        Setting::where('shop_id', $shopId)->delete();

        ActivityLog::log('update', null, null, null, "Réinitialisation des paramètres: {$shop->name}");

        return redirect()
            ->route('admin.settings.index', ['shop_id' => $shopId])
            ->with('success', "Paramètres de {$shop->name} réinitialisés aux valeurs globales.");
    }

    /**
     * Upload du logo de l'entreprise
     */
    public function uploadLogo(Request $request)
    {
        $request->validate([
            'logo' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'shop_id' => 'nullable|exists:shops,id',
        ]);

        $shopId = $request->shop_id;
        
        if ($request->hasFile('logo')) {
            $logoPath = $request->file('logo')->store('logos', 'public');
            
            // Sauvegarder dans les paramètres
            $conditions = ['key' => 'company_logo'];
            if ($shopId) {
                $conditions['shop_id'] = $shopId;
            } else {
                $conditions['shop_id'] = null;
            }
            
            Setting::updateOrCreate(
                $conditions,
                [
                    'value' => $logoPath,
                    'type' => 'string',
                    'group' => 'general',
                    'is_global' => $shopId ? false : true,
                ]
            );
        }

        $redirectUrl = route('admin.settings.index');
        if ($shopId) {
            $redirectUrl .= '?shop_id=' . $shopId;
        }

        return redirect($redirectUrl)->with('success', 'Logo téléchargé avec succès.');
    }

    /**
     * Créer une sauvegarde de la base de données
     */
    public function backup(Request $request)
    {
        try {
            $filename = 'backup_' . date('Y-m-d_His') . '.sql';
            $backupPath = storage_path('backups/' . $filename);
            
            // Créer le dossier si nécessaire
            if (!file_exists(storage_path('backups'))) {
                mkdir(storage_path('backups'), 0755, true);
            }
            
            // Exécuter mysqldump
            $database = config('database.connections.mysql.database');
            $username = config('database.connections.mysql.username');
            $password = config('database.connections.mysql.password');
            $host = config('database.connections.mysql.host');
            
            $command = sprintf(
                'mysqldump -h%s -u%s -p%s %s > %s',
                escapeshellarg($host),
                escapeshellarg($username),
                escapeshellarg($password),
                escapeshellarg($database),
                escapeshellarg($backupPath)
            );
            
            exec($command, $output, $returnVar);
            
            if ($returnVar === 0 && file_exists($backupPath)) {
                ActivityLog::log('create', null, null, null, "Sauvegarde créée: {$filename}");
                
                return response()->download($backupPath, $filename, [
                    'Content-Type' => 'application/sql',
                ]);
            }
            
            return back()->with('error', 'Erreur lors de la création de la sauvegarde.');
        } catch (\Exception $e) {
            return back()->with('error', 'Erreur: ' . $e->getMessage());
        }
    }

    /**
     * Gestion des modes de paiement
     */
    public function paymentMethods()
    {
        $paymentMethods = PaymentMethod::ordered()->get();
        return view('admin.settings.payment-methods', compact('paymentMethods'));
    }

    public function storePaymentMethod(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|unique:payment_methods,code',
            'icon' => 'nullable|string|max:50',
            'is_active' => 'nullable',
        ]);

        $validated['is_active'] = $request->has('is_active');
        $validated['type'] = 'cash'; // default type

        $paymentMethod = PaymentMethod::create($validated);

        ActivityLog::log('create', $paymentMethod, null, $paymentMethod->toArray(), "Création mode de paiement: {$paymentMethod->name}");

        return back()->with('success', 'Mode de paiement créé avec succès.');
    }

    public function updatePaymentMethod(Request $request, PaymentMethod $paymentMethod)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|unique:payment_methods,code,' . $paymentMethod->id,
            'type' => 'required|in:cash,mobile_money,card',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'sort_order' => 'integer|min:0',
        ]);

        $paymentMethod->update($validated);

        return back()->with('success', 'Mode de paiement mis à jour.');
    }

    public function destroyPaymentMethod(PaymentMethod $paymentMethod)
    {
        $paymentMethod->delete();
        return back()->with('success', 'Mode de paiement supprimé.');
    }
}
