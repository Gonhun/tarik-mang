<div>
    @if ($isModalOpen && $workflow)
        <div class="modal fade show d-block" tabindex="-1" style="background: rgba(30, 41, 59, 0.25);">
            <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable custom-modal-responsive" style="width: 100%; max-width: 720px; margin: 0 auto;">
                <div class="modal-content border-0 shadow-lg animate__animated animate__fadeInDown" style="width: 100%; margin: 0 auto; background: linear-gradient(135deg, #f8fafc 80%, #e5e7eb 100%);">
                    <div class="modal-header align-items-center" style="background: #f3f4f6; border-bottom: 1px solid #e5e7eb;">
                        <div class="d-flex align-items-center gap-2">
                            <span class="rounded-circle d-flex align-items-center justify-content-center" style="background: #e5e7eb; width: 40px; height: 40px;">
                                <i class="bx bx-sitemap fs-4 text-secondary"></i>
                            </span>
                            <h5 class="modal-title mb-0 fw-bold text-dark" style="letter-spacing: 0.01em;">Visualisasi Alur Approve</h5>
                        </div>
                        <button type="button" class="btn-close" wire:click="closeModal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body bg-white" style="background: linear-gradient(120deg, #f8fafc 90%, #e0e7ef 100%);">
                        <div class="mb-3 text-center">
                            <span class="badge px-4 py-2 fs-6 shadow-sm" style="background: #e5e7eb; color: #374151; border-radius: 1.5rem; font-size: 1.1rem; letter-spacing: 0.01em;">
                                <i class="bx bx-layer me-1"></i> {{ $workflow->name }} {{Auth::user()->jobsite->name}}
                            </span>
                        </div>
                        <p class="text-muted mb-4 text-center" style="font-size: 1.05rem;">Nama yang tercantum adalah yang <b>MUNGKIN</b> melakukan Approve</p>
                        
                        <!-- Timeline visualization -->
                        <div class="timeline-container">
                            <ul class="timeline">
                                @foreach ($workflow->steps as $step)
                                
                                    @if ($step->approverPositionLevel->name == 'Kepala Proyek' && in_array(Auth::user()->jobsite->code, ['SMD', 'JKT']))
                                    @else   
                                        @php
                                            $formalColors = [
                                                '#2563eb', // blue-600
                                                '#0e7490', // cyan-700
                                                '#334155', // slate-700
                                                '#7c3aed', // violet-600
                                                '#059669', // emerald-600
                                                '#b91c1c', // red-700
                                                '#be123c', // rose-700
                                                '#ca8a04', // amber-600
                                                '#0369a1', // sky-700
                                                '#4b5563', // gray-600
                                            ];
                                            $colorIndex = $step->id % count($formalColors);
                                            $randomFormalColor = $formalColors[$colorIndex];
                                        @endphp
                                        <li class="timeline-item">
                                            <div class="timeline-marker" style="background: {{ $randomFormalColor }};">
                                                <span>{{ $step->step_order }}</span>
                                            </div>
                                            <div class="timeline-content">
                                                <div class="d-flex align-items-center gap-2 mb-1">
                                                    <span class="timeline-icon" style="background: {{ $randomFormalColor }};">
                                                        <i class="bx bx-user-check" style="color: #fff;"></i>
                                                    </span>
                                                    <span class="fw-semibold" style="color: {{ $randomFormalColor }};">{{ $step->approverPositionLevel->name ?? 'Jabatan Tidak Ditemukan' }} Approver</span>
                                                </div>
                                                <div class="timeline-desc">
                                                    @if(isset($resolvedApprovers[$step->id]))
                                                        <div class="mt-2 pt-2 border-top">
                                                            <small class="text-muted">Akan disetujui oleh:</small>
                                                            <p class="mb-0 fw-bold">{!! $resolvedApprovers[$step->id] !!}</p>
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>
                                        </li> 
                                    @endif
                                    
                                @endforeach
                                <!-- Last step -->
                                <li class="timeline-item timeline-item-finish">
                                    <div class="timeline-marker timeline-marker-finish">
                                        <i class="bx bx-finis"></i>
                                    </div>
                                    <div class="timeline-content">
                                        <div class="d-flex align-items-center gap-2 mb-1">
                                            <span class="timeline-icon timeline-icon-finish">
                                                <i class="bx bxs-user-pin"></i>
                                            </span>
                                            <span class="fw-semibold" style="color: #3ba97b;">Selesai</span>
                                        </div>
                                        <div class="timeline-desc">
                                            Jika semua tahap disetujui, status pengajuan akan menjadi <strong class="text-success">"Approved"</strong>.
                                        </div>
                                    </div>
                                </li>
                            </ul>
                        </div>
                    </div>
                    <div class="modal-footer bg-white border-0">
                        <button type="button" class="btn btn-outline-secondary px-4" wire:click="closeModal" style="border-radius: 1.5rem;">
                            <i class="bx bx-x me-1"></i> Tutup
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>

@push('styles')
<style>
    .timeline-container {
        padding: 1.5rem 0;
        display: flex;
        justify-content: center;
    }
    .timeline {
        list-style: none;
        margin: 0;
        padding: 0;
        position: relative;
        max-width: 520px;
        width: 100%;
    }
    .timeline::before {
        content: '';
        position: absolute;
        left: 32px;
        top: 0;
        bottom: 0;
        width: 4px;
        background: linear-gradient(180deg, #e5e7eb 0%, #e0e7ef 100%);
        border-radius: 2px;
        z-index: 0;
    }
    .timeline-item {
        position: relative;
        display: flex;
        align-items: flex-start;
        margin-bottom: 2.2rem;
        min-height: 60px;
    }
    .timeline-item:last-child {
        margin-bottom: 0;
    }
    .timeline-marker {
        position: relative;
        z-index: 1;
        width: 36px;
        height: 36px;
        border-radius: 50%;
        background: #e2e8f0;
        color: #fff;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        font-size: 1.1rem;
        margin-right: 1.2rem;
        box-shadow: 0 2px 8px 0 rgba(100,116,139,0.08);
        border: 2px solid #fff;
        flex-shrink: 0;
    }
    .timeline-marker span {
        font-size: 1.1em;
        color: #fff;
    }
    .timeline-marker-finish {
        background: #e2e8f0 !important;
        color: #3ba97b !important;
        font-size: 1.3rem;
    }
    .timeline-content {
        flex: 1 1 0;
        min-width: 0;
        background: rgba(248,250,252,0.97);
        border-radius: 1rem;
        padding: 0.7rem 1.2rem;
        box-shadow: 0 1px 4px rgba(100,116,139,0.05);
        border: 1px solid #e5e7eb;
        position: relative;
    }
    .timeline-icon {
        width: 28px;
        height: 28px;
        border-radius: 50%;
        background: #e5e7eb;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.2rem;
    }
    .timeline-icon-finish {
        background: #e5e7eb !important;
        color: #3ba97b !important;
    }
    .timeline-desc {
        font-size: 0.98rem;
        color: #64748b;
        margin-top: 0.1rem;
        word-break: break-word;
    }
    .timeline-item-finish .timeline-content {
        background: #f3f4f6;
        border: 1px solid #d1d5db;
    }
    @media (max-width: 767.98px) {
        .timeline {
            max-width: 98vw;
        }
        .timeline-content {
            padding: 0.6rem 0.7rem;
            font-size: 0.95rem;
        }
        .timeline-marker, .timeline-marker-finish {
            width: 26px;
            height: 26px;
            font-size: 0.95rem;
        }
        .timeline-icon, .timeline-icon-finish {
            width: 20px;
            height: 20px;
            font-size: 1rem;
        }
    }
    @media (max-width: 575.98px) {
        .timeline {
            max-width: 100vw;
        }
        .timeline-content {
            padding: 0.4rem 0.4rem;
            font-size: 0.88rem;
        }
        .timeline-marker, .timeline-marker-finish {
            width: 18px;
            height: 18px;
            font-size: 0.8rem;
        }
        .timeline-icon, .timeline-icon-finish {
            width: 14px;
            height: 14px;
            font-size: 0.8rem;
        }
    }
</style>
@endpush