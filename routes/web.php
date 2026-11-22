<?php

// use App\Http\Controllers\AiInboundController;
use App\Http\Controllers\AssetLabelController;
use App\Http\Controllers\pdf\BastPrint;
use App\Http\Controllers\AssetTransactionController;
use App\Http\Controllers\pdf\MutasiController;
use App\Http\Controllers\pdf\PengembalianController;
use App\Livewire\Forecast\DetailManageForecast;
use App\Livewire\Forecast\ManageForecast;
use App\Livewire\Master\Forecast\Coa;
use App\Livewire\Master\Forecast\Forecast;
use App\Livewire\Master\Forecast\GeneratePivotCoa;
use App\Livewire\Master\Forecast\RefForecast;
use App\Livewire\Master\Forecast\SummaryBudget;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

Route::get('/', App\Livewire\Auth\Login::class)->name('login')->middleware('guest');
Route::get('/register', App\Livewire\Auth\Register::class)->name('register')->middleware('guest');
// Route::get('/test-livewire-config', function () {
//     dd(config('livewire.temporary_file_upload.preview_mimes'));
// });
Route::middleware('auth')->group(function () {
    Route::get('admin/roles-permissions', App\Livewire\Roles\ManageRolesPermissions::class)
        ->name('admin.roles-permissions')
        ->middleware('can:Add Permissions');
    Route::get('/organization-chart', App\Livewire\Profile\OrganizationalChart::class)
        ->name('organization-chart')
        ;

    Route::get('/admin/wa', App\Livewire\Admin\Whatsapp::class)->name('admin.wa')->middleware('can:Whatsapp');


    Route::get('/licenses', App\Livewire\Licenses\Main::class)->name('licenses.main')->middleware('can:Lisensi');
    Route::get('/licenses/category', App\Livewire\Licenses\Category\Main::class)->name('licenses.category')->middleware('can:Lisensi');
    Route::get('/licenses/manufacture', App\Livewire\Licenses\Manufacture\Main::class)->name('licenses.manufacture')->middleware('can:Lisensi');
    Route::get('/licenses/{id}', App\Livewire\Licenses\View::class)->name('licenses.detail')->whereNumber('id')->middleware('can:Lisensi');
    Route::get('/licenses/requests/add', App\Livewire\LicensesRequests\Request::class)->name('requests.license.add');
    Route::get('/licenses/requests', App\Livewire\LicensesRequests\Main::class)->name('requests.license.main');
    Route::get('/licenses/requests/renew/{id}', App\Livewire\LicensesRequests\RenewRequest::class)->name('requests.license.renew')->whereNumber('id');
    Route::get('/licenses/pdf/{id}', [App\Http\Controllers\pdf\requestLicensi::class, 'view'])->name('licenses.pdf')->whereNumber('id');

    Route::get('/isr', App\Livewire\Isr\Main::class)->name('Isr.main')->middleware('can:Isr');

    Route::get('/software', App\Livewire\Licenses\Software\Main::class)->name('software.main');

    Route::get('/admin/forigate', App\Livewire\Admin\Fortigate::class)->name('admin.fortigate')->middleware('can:Fortigate');
    Route::get('/admin/forigate/port', App\Livewire\Admin\Fortigate\Port::class)->name('admin.fortigate.port')->middleware('can:Fortigate');
    Route::get('/admin/forigate/api', App\Livewire\Admin\Fortigate\Api::class)->name('admin.fortigate.api')->middleware('can:Fortigate');

    Route::get('/admin/users', App\Livewire\Admin\ManageUsers::class)->name('admin.users')->middleware('can:Add User');

    Route::get('/admin/entitas', App\Livewire\Admin\ManageEntities::class)->name('admin.entitas')->middleware('can:Add Entitas');

    Route::get('/admin/logs', App\Livewire\Admin\AuditLogViewer::class)->name('admin.logs')->middleware('can:Logs');

    Route::get('/admin/jobsites', App\Livewire\Admin\ManageJobsites::class)->name('admin.jobsites')->middleware('can:Add Jobsites');

    Route::get('/admin/workflow', App\Livewire\ApprovalWorkflows\ManageAssignments::class)->name('admin.workflow')->middleware('can:Add Workflow');
    Route::get('/admin/workflow/step', App\Livewire\ApprovalWorkflows\ManageWorkflows::class)->name('admin.workflow.add')->middleware('can:Add Workflow');

    Route::get('/admin/departements', App\Livewire\Admin\ManageDepartments::class)->name('admin.dept')->middleware('can:Add Dept');
    Route::get('/admin/jabatan', App\Livewire\Admin\ManagePositions::class)->name('admin.jabatan')->middleware('can:Add Jabatan');
    Route::get('/admin/jabatan/levels', App\Livewire\Admin\ManageLevel::class)->name('admin.jabatan.levels')->middleware('can:Add Jabatan');
    Route::get('/admin/position-standards', App\Livewire\Admin\ManagePositionStandards::class)->name('admin.standar-asset')->middleware('can:Manajement IT');
    Route::get('/asset/type', App\Livewire\Master\Asset\Type::class)->name('admin.asset-type')->middleware('can:Add Type');
    Route::get('/asset/brand', App\Livewire\Master\Asset\Brand::class)->name('admin.asset-brand')->middleware('can:Add Brands');
    Route::get('/asset/series', App\Livewire\Master\Asset\Series::class)->name('admin.asset-series')->middleware('can:Add Series');
    Route::get('/admin/cost-center', App\Livewire\CostCenters\ManageCostCenters::class)->name('cc.cost-center')->middleware('can:Add Cost Center');


    Route::get('/maps', App\Livewire\GpsMapViewer::class)->name('GpsMapViewer');
    Route::get('/dashboard', App\Livewire\Dashboard\Index::class)->name('dashboard'); // Misal halaman utama setelah login adalah absensi
    Route::get('/my/asset', App\Livewire\Master\Asset\MyAssets::class)->name('my.asset');
    Route::get('/my/clearance', App\Livewire\ItClearanceForm::class)->name('my.clearance');
    Route::get('/asset', App\Livewire\Master\Asset\ManageAsset::class)->name('asset.index')->middleware('can:Master Asset');
    Route::get('/asset/bast/{id}', [BastPrint::class, 'view'])->whereNumber('id')->name('asset.bast')->middleware('can:Master Asset');
    Route::get('/asset/damage', App\Livewire\Master\Asset\Damages\ManageDamageReports::class)->name('asset.damage')->middleware('can:Master Asset');
    Route::get('/asset/pengembalian/{id}', [PengembalianController::class, 'view'])->whereNumber('id')->name('asset.pengembalian')->middleware('can:Master Asset');
    Route::get('/asset/mutasi/{id}', [MutasiController::class, 'view'])
        ->whereNumber('id')
        ->name('asset.mutasi.pdf')
        ->middleware('can:Master Asset');
    // Cetak (ulang) berita acara per transaksi, termasuk BA lama. Otorisasi di controller.
    Route::get('/asset/ba/{transaction}/{asset}', [App\Http\Controllers\pdf\AssetBaPrint::class, 'view'])
        ->whereNumber(['transaction', 'asset'])
        ->name('asset.ba.print');
    Route::get('/asset/qc', App\Livewire\Master\Asset\QcAsset::class)->name('asset.qc')->middleware('can:Master Asset');

    Route::get('/asset/disposed', App\Livewire\AssetDisposals\ManageDisposed::class)->name('asset.disposed')->middleware('can:Mutasi');

    Route::get('/asset/mutasi', App\Livewire\AssetMutations\ManageMutations::class)->name('asset.mutasi')->middleware('can:Mutasi');

    // Approver (mis. disposal/kehilangan) belum tentu punya izin Mutasi: cek lewat gate transaksi.
    Route::get('/asset/transaksi/show/{transaction}', App\Livewire\AssetMutations\ShowAssetTransaction::class)->name('asset.mutasi-status')->middleware('can:view-asset-transaction,transaction');
    Route::get('/asset/show/{asset:asset_number}', App\Livewire\Master\Asset\AssetDetail::class)->name('assets.show');
    Route::get('/assets/{asset}/print-label', [AssetLabelController::class, 'printSingle'])
        ->name('assets.print-single-label')->middleware('can:print-asset-label,asset');
    Route::get('/asset-transactions/{transaction}/print', [AssetTransactionController::class, 'print'])
        ->name('asset-transactions.print')->middleware('can:view-asset-transaction,transaction');
    Route::get('/myprofil', App\Livewire\Profile\Profil::class)->name('myprofil');

    Route::middleware('can:Master Asset')->group(function () {
        Route::get('/coa/pivot', GeneratePivotCoa::class)->name(name: 'master.forecast.pivot');
        Route::get('/coa', Coa::class)->name('coa.index');
        Route::get('/master/forecast', RefForecast::class)->name('forecast.index');
        Route::get('/forecast/manage', ManageForecast::class)->name('forecast.manage');
        Route::get('/forecast/detail/{id}', DetailManageForecast::class)->name('forecast.detail');
    });

    Route::get('/task', App\Livewire\Task\TaskList::class)->name('task.index');
    Route::get('/task/create', App\Livewire\Task\TaskCreate::class)->name('task.create');

    Route::get('/asset-requests', App\Livewire\AssetRequests\CreateAssetRequestForm::class)->name('asset-requests.create');
    Route::get('/asset-requests/dashboard', App\Livewire\AssetRequests\Dashboard::class)->name('asset-requests.dashboard');
    Route::get('/asset-requests/print/{id}', [App\Http\Controllers\pdf\AssetRequestController::class, 'view'])->whereNumber('id')->name('asset-requests.print');
    Route::get('/asset-requests/show/{assetRequest}', App\Livewire\AssetRequests\ShowAssetRequest::class)->name('asset-requests.show');
    Route::get('/asset-requests/pr', App\Livewire\Admin\AssetRequest\Administrator::class)->name('request.asset-prsap')->middleware('can:Manajement Request');
    Route::get('/asset-requests/po', App\Livewire\Admin\AssetRequest\Importpo::class)->name('request.asset-po')->middleware('can:Manajement Request');
    Route::get('/asset-requests/po-returns', App\Livewire\Procurement\ManageVendorReturns::class)->name('request.asset-po-returns')->middleware('can:Manajement Request');
    // Route::get('/bast', App\Livewire\AssetHandovers\ManageAssetHandovers::class)->name('bast.index'); 
    Route::get('/bast', App\Livewire\AssetTransactions\ManageAssetTransactions::class)->name('bast.index');

    Route::get('/games/tetris', App\Livewire\Games\Tetris::class)
        ->name('games.tetris');

    // Inventory Consumabel
    Route::get('/inventory/master-parts', App\Livewire\Inventory\Consumables\MaseterParts::class)->name('inventory.master.parts')->middleware('can:Master Asset');
    Route::get('/inventory/parts', App\Livewire\Inventory\Consumables\Index::class)->name('inventory.parts')->middleware('can:Master Asset');
    // Tickets Route
    // Route::get('/tickets', function(){
    //     return 'Tickets OK';
    // });
    Route::get('/tickets/ud', App\Livewire\Tickets\UnderDeveloper::class)
        ->name('tickets.under-developer');
    Route::get('/tickets/home', App\Livewire\Tickets\Home\Index::class)
        ->name('tickets.home');
    Route::get('/tickets/categorys', App\Livewire\Tickets\Categorys\Index::class)
        ->name('tickets.categorys')->middleware('can:it-staff');
    Route::get('/tickets/subcategorys', App\Livewire\Tickets\Subcategorys\Index::class)
        ->name('tickets.subcategorys')->middleware('can:it-staff');

    Route::get('/tickets/my-tickets', App\Livewire\Tickets\Ticket\MyTickets::class)
        ->name('tickets.ticket.my-tickets');
    Route::get('/tickets/detail/{ticket:ticket_no}', App\Livewire\Tickets\Ticket\Detail::class)
        ->name('tickets.ticket.detail')->whereAlphaNumeric('ticket_no');

    //AI
    // Route::get('/ai/chat/{chat?}', App\Livewire\AI\Chat::class)->name('ai.chat');
    // File upload (disk public) hanya untuk user yang login. Lihat StorageFileController.
    Route::get('/storage/{path}', [App\Http\Controllers\StorageFileController::class, 'show'])
        ->where('path', '.*')
        ->name('storage.public');

    Route::post('/logout', function (Request $request) {
        Auth::logout(); // Atau Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/'); // Atau redirect()->route('login');
    })->name('logout');
});
// Route::post('/webhook/n8n/from-agent', [AiInboundController::class, 'receive'])->name('webhook.n8n.receive');
// // Route::get('/', function () {
// //     return view('welcome');
// // });
