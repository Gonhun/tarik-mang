<div class="content-wrapper d-flex align-items-center justify-content-center" style="min-height: 80vh;">
    <div class="row w-100 justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="card shadow border-0">
                <div class="card-body text-center py-5 px-4">
                    <img 
                        src="https://cdn-icons-png.flaticon.com/512/5956/5956662.png" 
                        alt="Under Development" 
                        style="width: 100px; opacity: 0.7;" 
                        class="mb-4"
                    >
                    <h2 class="mb-3 font-weight-bold text-dark">Halaman Dalam Pengembangan</h2>
                    <p class="text-secondary mb-4" style="font-size: 1.1rem;">
                        Mohon maaf, halaman yang Anda tuju sedang dalam proses pengembangan.<br>
                        Silakan kembali lagi di lain waktu.
                    </p>
                    <a 
                        href="{{ url()->previous() }}" 
                        class="btn btn-primary px-4 py-2"
                        style="font-size: 1rem;"
                    >
                        <i class="mdi mdi-arrow-left mr-2"></i>
                        Kembali
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
