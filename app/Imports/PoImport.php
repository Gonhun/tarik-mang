<?php

namespace App\Imports;

use App\Models\AssetRequestItem;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\RemembersRowNumber;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class PoImport implements ToModel,WithBatchInserts,WithChunkReading,WithHeadingRow
{
    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */
    use RemembersRowNumber;
    
    public function model(array $row)
    {
        $currentRowNumber = $this->getRowNumber();

        try {
            $item = AssetRequestItem::where([
                ['pr_number', $row['purch_req']],
                ['fulfillment_type', 'NEW_PURCHASE'],
                ['qc_status', 'Pending'],
            ])->where(function ($query) {
                $query->whereNull('gr_number')
                      ->orWhereNull('po_number');
            })->first();
            Log::warning('query', [
                'query' =>  $item,
            ]);
           
            if ($item) {
                $prdate = Carbon::instance(Date::excelToDateTimeObject($row['pr_date']));
                $prdaterelease = Carbon::instance(Date::excelToDateTimeObject($row['pr_release_date']));
                $podate = Carbon::instance(Date::excelToDateTimeObject($row['po_date']));
                $deliverydate = Carbon::instance(Date::excelToDateTimeObject($row['delivery_date']));
                $AR = $item->assetRequest;
                if(!empty($row['gr_number']))
                {
                    $AR->update([
                        'pr_number' => $row['purch_req'],
                        'status'=> 'Pending QC',
                    ]);
                }
                $item->update([
                    'po_number' => $row['po_number'],
                    'gr_number' => !empty($row['gr_number']) ? $row['gr_number'] : null,
                    // 'price' => $row['price'],
                    // 'procurement_notes' => 'PR Date  '.$prdate->format('Y-m-d').' PR Release Date '.$prdaterelease->format('Y-m-d').' PO Date '.$podate->format('Y-m-d').' Delivery Date '.$deliverydate->format('Y-m-d').'.',
                ]);
            } else {
                // Log jika item tidak ditemukan
                Log::warning('PO Import gagal: AssetRequestItem tidak ditemukan.', [
                    'row_number' => $currentRowNumber,
                    'pr_number' => $row['purch_req'] ?? null,
                    'row_data' => $row,
                ]);
            }
        } catch (\Throwable $e) {
            // Log error jika terjadi exception
            Log::error('PO Import error pada baris ' . $currentRowNumber . ': ' . $e->getMessage(), [
                'row_number' => $currentRowNumber,
                'row_data' => $row,
                'exception' => $e,
            ]);
        }
    }

    public function batchSize(): int
    {
        return 1000;
    }
    public function chunkSize(): int
    {
        return 1000;
    }
}
