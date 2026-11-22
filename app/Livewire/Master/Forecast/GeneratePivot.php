<?php

namespace App\Livewire\Master\Forecast;

use App\Models\Coa;
use App\Models\Department;
use App\Models\Jobsite;
use App\Models\MasterPivot;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Lazy;
use Livewire\Component;
use Livewire\WithPagination;

class GeneratePivot extends Component
{
    use WithPagination;

    public $dept_user, $site_user;
    public $departements = [], $jobsites = [];
    public $dept_filter, $site_filter, $year_filter;
    public $id_pivot, $cost_element_number, $description, $department_id, $jobsite_id;
    public $year, $planJan, $actJan, $planFeb, $actFeb, $planMar, $actMar, $planApr, $actApr, $planMay, $actMay, $planJun, $actJun,
    $planJul, $actJul, $planAug, $actAug, $planSep, $actSep, $planOct, $actOct, $planNov, $actNov, $planDec, $actDec;
    public $pivot_draft;
    public $search = '';
    public $data_show = 10;
    public $isModalOpen;

    #[Lazy]
    public function mount()
    {
        $this->year_filter = date('Y');
        $this->departements = Department::orderBy('code')->get(['id', 'name', 'code']);
        $this->jobsites = Jobsite::orderBy('code')->get(['id', 'name', 'code']);
        $this->dept_user = Auth::user()->department_id;
        $this->site_user = Auth::user()->jobsite_id;
        $this->dept_filter = Auth::user()->department_id;
        $this->site_filter = Auth::user()->jobsite_id;

    }

    public function render()
    {
        return view('livewire.master.forecast.generate-pivot');
    }

    protected function rules()
    {
        return [
            'cost_element_number' => [
                'required',
                'max:255',
            ],
            'description' => [
                'required',
                'max:255'
            ],
            'department_id' => 'required|exists:departments,id',
            'jobsite_id' => 'required|exists:jobsites,id',
            'year' => 'required|numeric',
            'planJan' => 'nullable|numeric',
            'actJan' => 'nullable|numeric',
            'planFeb' => 'nullable|numeric',
            'actFeb' => 'nullable|numeric',
            'planMar' => 'nullable|numeric',
            'actMar' => 'nullable|numeric',
            'planApr' => 'nullable|numeric',
            'actApr' => 'nullable|numeric',
            'planMay' => 'nullable|numeric',
            'actMay' => 'nullable|numeric',
            'planJun' => 'nullable|numeric',
            'actJun' => 'nullable|numeric',
            'planJul' => 'nullable|numeric',
            'actJul' => 'nullable|numeric',
            'planAug' => 'nullable|numeric',
            'actAug' => 'nullable|numeric',
            'planSep' => 'nullable|numeric',
            'actSep' => 'nullable|numeric',
            'planOct' => 'nullable|numeric',
            'actOct' => 'nullable|numeric',
            'planNov' => 'nullable|numeric',
            'actNov' => 'nullable|numeric',
            'planDec' => 'nullable|numeric',
            'actDec' => 'nullable|numeric',
        ];
    }

    protected $messages = [
        'cost_element_number.required' => 'Nomor Cost Element wajib diisi.',
        'cost_element_number.max' => 'Nomor Cost Element maksimal 255 karakter.',
        'description.required' => 'Deskripsi harus diisi.',
        'description.max' => 'Deskripsi maksimal 255 karakter.',
        'department_id.required' => 'Departemen harus diisi.',
        'department_id.exists' => 'Departemen tidak valid untuk cost element.',
        'jobsite_id.required' => 'Jobsite harus diisi.',
        'jobsite_id.exists' => 'Jobsite tidak valid untuk cost element.',
        'year.required' => 'Tahun harus diisi.',
        'year.numeric' => 'Tahun harus berupa angka',
        'planJan.numeric' => 'Nilai Januari harus berupa angka.',
        'actJan.numeric' => 'Nilai Januari harus berupa angka.',
        'planFeb.numeric' => 'Nilai Februari harus berupa angka.',
        'actFeb.numeric' => 'Nilai Februari harus berupa angka.',
        'planMar.numeric' => 'Nilai Maret harus berupa angka.',
        'actMar.numeric' => 'Nilai Maret harus berupa angka.',
        'planApr.numeric' => 'Nilai April harus berupa angka.',
        'actApr.numeric' => 'Nilai April harus berupa angka.',
        'planMay.numeric' => 'Nilai Mei harus berupa angka.',
        'actMay.numeric' => 'Nilai Mei harus berupa angka.',
        'planJun.numeric' => 'Nilai Juni harus berupa angka.',
        'actJun.numeric' => 'Nilai Juni harus berupa angka.',
        'planJul.numeric' => 'Nilai Juli harus berupa angka.',
        'actJul.numeric' => 'Nilai Juli harus berupa angka.',
        'planAug.numeric' => 'Nilai Agustus harus berupa angka.',
        'actAug.numeric' => 'Nilai Agustus harus berupa angka.',
        'planSep.numeric' => 'Nilai September harus berupa angka.',
        'actSep.numeric' => 'Nilai September harus berupa angka.',
        'planOct.numeric' => 'Nilai Oktober harus berupa angka.',
        'actOct.numeric' => 'Nilai Oktober harus berupa angka.',
        'planNov.numeric' => 'Nilai November harus berupa angka.',
        'actNov.numeric' => 'Nilai November harus berupa angka.',
        'planDec.numeric' => 'Nilai Desember harus berupa angka.',
        'actDec.numeric' => 'Nilai Desember harus berupa angka.',
    ];

    public function openModal($id, $coa, $name)
    {
        $this->isModalOpen = true;
        if ($id != 0) {
            $this->id_pivot = $id;
            $data = MasterPivot::find($id);
            $this->planJan = $data->plan_jan ?? 0;
            $this->actJan = $data->act_jan ?? 0;
            $this->planFeb = $data->plan_feb ?? 0;
            $this->actFeb = $data->act_feb ?? 0;
            $this->planMar = $data->plan_mar ?? 0;
            $this->actMar = $data->act_mar ?? 0; 
            $this->planApr = $data->plan_apr ?? 0;
            $this->actApr = $data->act_apr ?? 0;
            $this->planMay = $data->plan_may ?? 0;
            $this->actMay = $data->act_may ?? 0;
            $this->planJun = $data->plan_jun ?? 0;
            $this->actJun = $data->act_jun ?? 0;
            $this->planJul = $data->plan_jul ?? 0;
            $this->actJul = $data->act_jul ?? 0;
            $this->planAug = $data->plan_aug ?? 0;
            $this->actAug = $data->act_aug ?? 0;
            $this->planSep = $data->plan_sep ?? 0;
            $this->actSep = $data->act_sep ?? 0;
            $this->planOct = $data->plan_oct ?? 0;
            $this->actOct = $data->act_oct ?? 0;
            $this->planNov = $data->plan_nov ?? 0;
            $this->actNov = $data->act_nov ?? 0;
            $this->planDec = $data->plan_dec ?? 0;
            $this->actDec = $data->act_dec ?? 0;
        }
        $this->cost_element_number = $coa;
        $this->description = $name;
        $this->year = $this->year_filter;
        $this->department_id = $this->dept_filter;
        $this->jobsite_id = $this->site_filter;
        $this->dispatch('open-modal', true);
    }

    public function closeModal()
    {
        $this->isModalOpen = false;
    }

    public function queryCoa(): Builder
    {
        return Coa::query();
    }

    public function queryMaster(): Builder
    {
        return MasterPivot::query();
    }

    public function queryPivot()
    {
        return DB::table('coas')
            ->leftJoin('master_pivots as pivots', function($join) {
                $join->on('coas.cost_element_number', '=', 'pivots.cost_element_number')
                     ->where('pivots.year', '=', $this->year_filter);
            })
            ->select(
                'pivots.id',
                DB::raw('CASE WHEN year is null THEN '.$this->year_filter .' ELSE pivots.year END as year'),
                'coas.cost_element_number',
                'coas.description',
                'pivots.jobsite_id',
                'pivots.department_id',
                'pivots.plan_jan',
                'pivots.act_jan',
                'pivots.plan_feb',
                'pivots.act_feb',
                'pivots.plan_mar',
                'pivots.act_mar',
                'pivots.plan_apr',
                'pivots.act_apr',
                'pivots.plan_may',
                'pivots.act_may',
                'pivots.plan_jun',
                'pivots.act_jun',
                'pivots.plan_jul',
                'pivots.act_jul',
                'pivots.plan_aug',
                'pivots.act_aug',
                'pivots.plan_sep',
                'pivots.act_sep',
                'pivots.plan_oct',
                'pivots.act_oct',
                'pivots.plan_nov',
                'pivots.act_nov',
                'pivots.plan_dec',
                'pivots.act_dec'
            );
    }

    public function datas()
    {
        $query = $this->queryPivot()->get();
        // ->when(function ($q) {
        //     if (!empty($this->dept_filter)) {
        //         $q->where('department_id', $this->dept_filter);
        //     }
        //     if (!empty($this->site_filter)) {
        //         $q->where('jobsite_id', $this->site_filter);
        //     }
        //     if (!empty($this->year_filter)) {
        //         $q->where('year', $this->year_filter);
        //     }
        //     if (!empty($this->search)) {
        //         $searchTerm = '%' . $this->search . '%';
        //         $q->where('cost_element_number', 'like', $searchTerm)
        //             ->orWhere('description', 'like', $searchTerm);
        //     }
        // })


        // if ($paginated->count() > 0) {
        $perPage = 10;
        $currentPage = Paginator::resolveCurrentPage() ?: 1;

        // Do not assign paginator object to a public property, just return it
        $paginated = new LengthAwarePaginator(
            $query->forPage($currentPage, $perPage),
            $query->count(),
            $perPage,
            $currentPage,
            ['path' => Paginator::resolveCurrentPath()]
        );
        return $paginated;
        // } else {
        //     if ($this->dept_filter && $this->site_filter && $this->year_filter) {
        //         $this->pivot_draft = $this->queryPivot()->get();
        //         //     // Example: add months as columns to each Coa record
        //         //     $coas = $this->queryCoa()->get();
        //         //     // Get all month short names (e.g., Jan, Feb, ...)
        //         //     $months = [];
        //         //     for ($m = 1; $m <= 12; $m++) {
        //         //         $months[] = substr(date('F', mktime(0, 0, 0, $m, 1)), 0, 3);
        //         //     }
        //         //     // Add month columns with null/default values
        //         //     $coas->transform(function ($item) use ($months) {
        //         //         foreach ($months as $month) {
        //         //             $item->{$month} = null; // or set a default value
        //         //         }
        //         //         return $item;
        //         //     });

        //         //     $perPage = 10;
        //         //     $currentPage = Paginator::resolveCurrentPage() ?: 1;

        //         //     $this->pivot_draft = collect($coas);
        //         //     $paginatedData = new LengthAwarePaginator(
        //         //         $this->pivot_draft->forPage($currentPage, $perPage),
        //         //         $this->pivot_draft->count(),
        //         //         $perPage,
        //         //         $currentPage,
        //         //         ['path' => Paginator::resolveCurrentPath()]
        //         //     );

        //         return $this->pivot_draft;
        //     } else {
        //         return collect();
        //     }
        // }
    }

    public function store()
    {
        try {

            $validatedData = $this->validate($this->rules(), $this->messages);

            // dd($validatedData);
            $dataToStore = [
                'cost_element_number' => $this->cost_element_number,
                'description' => $this->description,
                'department_id' => $this->department_id,
                'jobsite_id' => $this->jobsite_id,
                'year' => $this->year,
                'act_jan' => $this->actJan ?? 0,
                'plan_jan' => $this->planJan ?? 0,
                'act_feb' => $this->actFeb ?? 0,
                'plan_feb' => $this->planFeb ?? 0,
                'act_mar' => $this->actMar ?? 0,
                'plan_mar' => $this->planMar ?? 0,
                'act_apr' => $this->actApr ?? 0,
                'plan_apr' => $this->planApr ?? 0,
                'act_may' => $this->actMay ?? 0,
                'plan_may' => $this->planMay ?? 0,
                'act_jun' => $this->actJun ?? 0,
                'plan_jun' => $this->planJun ?? 0,
                'act_jul' => $this->actJul ?? 0,
                'plan_jul' => $this->planJul ?? 0,
                'act_aug' => $this->actAug ?? 0,
                'plan_aug' => $this->planAug ?? 0,
                'act_sep' => $this->actSep ?? 0,
                'plan_sep' => $this->planSep ?? 0,
                'act_oct' => $this->actOct ?? 0,
                'plan_oct' => $this->planOct ?? 0,
                'act_nov' => $this->actNov ?? 0,
                'plan_nov' => $this->planNov ?? 0,
                'act_dec' => $this->actDec ?? 0,
                'plan_dec' => $this->planDec ?? 0,
            ];

            if ($this->id_pivot) {
                // dd($this->id_pivot);
                // Update existing record
                MasterPivot::where('id', $this->id_pivot)->update($dataToStore);
                $this->dispatch('swal:success', ['title' => 'Sukses', 'text' => 'Cost element berhasil diperbarui.']);
            } else {
                // Create new record
                // dd($dataToStore);
                MasterPivot::create($dataToStore);
                $this->dispatch('swal:success', ['title' => 'Sukses', 'text' => 'Cost element berhasil ditambahkan.']);
            }

            $this->closeModal();
        } catch (\Exception $e) {
            $this->closeModal();
            // untuk error lain seperti DB, logic, dll
            $this->dispatch('swal:error', ['title' => 'Error', 'text' => $e->getMessage()]);
        }
    }
}
