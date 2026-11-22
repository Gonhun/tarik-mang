<div>
    <style>
        /* Responsive styling untuk container chart */
        #org-chart-container {
            width: 100%;
            min-width: 180px;
            min-height: 120px;
            height: 60vh;
            max-height: 80vh;
            overflow-x: auto;
            overflow-y: auto;
            border: none;
            border-radius: 1rem;
            background: linear-gradient(135deg, #f8fafc 0%, #e9ecef 100%);
            box-shadow: 0 6px 32px 0 rgba(60,72,88,0.10);
            transition: box-shadow 0.3s;
            display: block;
        }
        @media (max-width: 1200px) {
            #org-chart-container {
                height: 50vh;
                max-height: 65vh;
            }
        }
        @media (max-width: 992px) {
            #org-chart-container {
                min-height: 120px;
                height: 40vh;
                max-height: 55vh;
                border-radius: 0.75rem;
            }
        }
        @media (max-width: 768px) {
            #org-chart-container {
                min-height: 100px;
                height: 32vh;
                max-height: 40vh;
                border-radius: 0.5rem;
            }
        }
        @media (max-width: 480px) {
            #org-chart-container {
                min-width: 120px;
                min-height: 80px;
                height: 24vh;
                max-height: 30vh;
                border-radius: 0.35rem;
                padding: 0;
            }
        }
        .org-chart-node {
            padding: 18px 16px;
            display: flex;
            align-items: center;
            gap: 16px;
            border-radius: 16px;
            background: linear-gradient(120deg, #fff 60%, #f1f3f6 100%);
            color: #23272f;
            border: none;
            box-shadow: 0 2px 12px 0 rgba(60,72,88,0.08);
            transition: box-shadow 0.25s, transform 0.18s;
            width: 100%;
            height: 100%;
            position: relative;
            min-width: 180px;
            max-width: 98vw;
            box-sizing: border-box;
        }
        .org-chart-node:hover {
            box-shadow: 0 8px 24px 0 rgba(60,72,88,0.18);
            transform: translateY(-2px) scale(1.03);
            z-index: 2;
        }
        .org-chart-node img {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid #e9ecef;
            box-shadow: 0 2px 8px rgba(0,0,0,0.10);
            background: #f8fafc;
            transition: border-color 0.2s;
        }
        .org-chart-node:hover img {
            border-color: #0d6efd;
        }
        .org-chart-node .info {
            display: flex;
            flex-direction: column;
            gap: 2px;
        }
        .org-chart-node .name {
            font-weight: 700;
            font-size: 1.1rem;
            font-family: 'Inter', 'Segoe UI', Arial, sans-serif;
            color: #0d6efd;
            letter-spacing: 0.01em;
        }
        .org-chart-node .position {
            font-size: 0.98rem;
            color: #495057;
            font-family: 'Inter', 'Segoe UI', Arial, sans-serif;
            font-weight: 500;
        }
        .org-chart-node .department {
            font-size: 0.85rem;
            color: #adb5bd;
            font-family: 'Inter', 'Segoe UI', Arial, sans-serif;
            font-style: italic;
            margin-top: 2px;
        }
        @media (max-width: 600px) {
            .org-chart-node {
                flex-direction: column;
                align-items: flex-start;
                padding: 12px 8px;
                gap: 8px;
                min-width: 120px;
            }
            .org-chart-node img {
                width: 44px;
                height: 44px;
            }
            .org-chart-node .name {
                font-size: 1rem;
            }
            .org-chart-node .position {
                font-size: 0.92rem;
            }
        }
        /* Responsive font and node size for very small screens */
        @media (max-width: 400px) {
            .org-chart-node {
                padding: 8px 4px;
                gap: 4px;
                min-width: 90px;
            }
            .org-chart-node img {
                width: 32px;
                height: 32px;
            }
            .org-chart-node .name,
            .org-chart-node .position,
            .org-chart-node .department {
                font-size: 0.85rem;
            }
        }
    </style>
    <div class="container-fluid py-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Struktur Organisasi Interaktif</h5>
            </div>
            <div class="card-body">
                {{-- Gunakan wire:ignore agar Livewire tidak mengganggu chart ini --}}
                <div wire:ignore id="org-chart-container"></div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    // Fungsi untuk mendapatkan nodeWidth dan nodeHeight yang responsif
    function getResponsiveNodeSize() {
        const w = window.innerWidth;
        if (w < 400) return { nodeWidth: 120, nodeHeight: 60 };
        if (w < 600) return { nodeWidth: 160, nodeHeight: 70 };
        if (w < 992) return { nodeWidth: 200, nodeHeight: 80 };
        return { nodeWidth: 240, nodeHeight: 90 };
    }

    document.addEventListener('livewire:init', () => {
        const chartData = @json($chartData);
        const chartContainer = document.getElementById('org-chart-container');

        function renderChart() {
            if (chartContainer.clientHeight > 0) {
                const { nodeWidth, nodeHeight } = getResponsiveNodeSize();
                const options = {
                    contentKey: 'data',
                    width: chartContainer.clientWidth,
                    height: chartContainer.clientHeight,
                    nodeWidth: nodeWidth,
                    nodeHeight: nodeHeight,
                    fontColor: '#333',
                    borderColor: '#adb5bd',
                    childrenSpacing: 60,
                    siblingSpacing: 30,
                    direction: 'top',
                    nodeTemplate: (content) => 
                    
                    `
                    <div 
                        class="org-chart-node modern-org-chart shadow-lg rounded-4 p-3 d-flex align-items-center gap-3 position-relative
                            ${content.id == {{ auth()->id() }} ? 'org-chart-node-selected' : ''}"
                        style="
                            border-top: 6px solid ${content?.options?.nodeBGColor || '#6c757d'};
                            background: linear-gradient(120deg, #fff 65%, ${content?.options?.nodeBGColor || '#e9ecef'}22 100%);
                            transition: box-shadow 0.25s, transform 0.18s;
                            min-width: 120px;
                            max-width: 98vw;
                            box-sizing: border-box;
                            box-shadow: 0 4px 24px 0 rgba(0,0,0,0.08), 0 1.5px 4px 0 rgba(0,0,0,0.04);
                            ${content.id == {{ auth()->id() }} ? 'outline: 3px solid #0d6efd; outline-offset: 2px; box-shadow: 0 0 0 4px #0d6efd33;' : ''}
                        "
                        ${content.id == {{ auth()->id() }} ? 'data-selected="true"' : ''}
                    >
                        <div class="org-chart-avatar flex-shrink-0 d-flex align-items-center justify-content-center"
                            style="
                                width: 64px; height: 64px; border-radius: 50%; overflow: hidden;
                                border: 4px solid ${content?.options?.nodeBGColor || '#6c757d'};
                                background: linear-gradient(135deg, #fff 60%, ${content?.options?.nodeBGColor || '#e9ecef'}33 100%);
                                box-shadow: 0 2px 8px 0 rgba(0,0,0,0.07);
                            ">
                            <img src="${content.imageURL}" alt="${content.name}" style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%;" />
                        </div>
                        <div class="org-chart-info flex-grow-1">
                            <div class="fw-bold name mb-1" style="font-size: 1.13rem; color: #1a1a1a; letter-spacing: 0.01em;">
                                ${content.id == {{ auth()->id() }} ? '' : content.name}
                                ${content?.isRoot ? `<span class='badge bg-primary ms-2' style='font-size:0.75em;'>Root</span>` : ''}
                                ${content.id == {{ auth()->id() }} ? `<span class='badge bg-info ms-2' style='font-size:0.75em;'>You</span>` : ''}
                            </div>
                            <div class="position text-primary-emphasis mb-1" style="font-size: 1.01rem; font-weight: 600;">
                                <i class="bx bx-briefcase-alt-2 me-1"></i>
                                ${content.position || '<span class="text-muted">-</span>'}
                            </div>
                            <div class="department text-secondary" style="font-size: 0.95rem;">
                                <i class="bx bx-building me-1"></i>
                                ${content.department || '<span class="text-muted">-</span>'}
                            </div>
                        </div>
                        <div class="org-chart-actions position-absolute end-0 top-0 p-2" style="z-index:2;">
                            <span class="badge rounded-pill" style="background:${content?.options?.nodeBGColor || '#adb5bd'}22; color:${content?.options?.nodeBGColor || '#495057'}; font-size:0.85em;">
                                <i class="bx bx-user me-1"></i>
                            </span>
                        </div>
                    </div>
                    `,
                    canvasStyle: 'background: #f8f9fa;',
                    enableToolbar: true,
                };

                // Hapus chart lama jika ada
                if (window._apexTreeInstance) {
                    window._apexTreeInstance.destroy && window._apexTreeInstance.destroy();
                }
                // Render Chart
                window._apexTreeInstance = new ApexTree(chartContainer, options);
                window._apexTreeInstance.render(chartData);
            } else {
                console.error("Container untuk chart (#org-chart-container) tidak memiliki tinggi saat diinisialisasi.");
            }
        }

        renderChart();

        // Buat chart responsif saat ukuran window berubah
        window.addEventListener('resize', () => {
            renderChart();
        });
    });
</script>
@endpush