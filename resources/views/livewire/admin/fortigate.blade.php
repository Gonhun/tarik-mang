<div>
    <style>
        .fortigate-hero {
            display: flex;
            flex-direction: column;
            align-items: center;
            margin-top: 40px;
            margin-bottom: 32px;
            animation: fadeInDown 1s;
        }
        .fortigate-logo {
            width: 64px;
            height: 64px;
            background: linear-gradient(135deg, #e53935 0%, #b71c1c 100%);
            border-radius: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 18px;
            box-shadow: 0 4px 24px rgba(229,57,53,0.13);
            animation: popIn 0.7s;
        }
        .fortigate-logo i {
            color: #fff;
            font-size: 2.5rem;
        }
        .fortigate-title {
            font-size: 2.2rem;
            font-weight: 800;
            letter-spacing: 0.04em;
            color: #2d3748;
            margin-bottom: 6px;
            text-shadow: 0 2px 8px rgba(44,62,80,0.07);
        }
        .fortigate-desc {
            color: #6c757d;
            font-size: 1.08rem;
            margin-bottom: 0;
            font-weight: 500;
            letter-spacing: 0.01em;
        }
        @keyframes fadeInDown {
            from { opacity: 0; transform: translateY(-30px);}
            to { opacity: 1; transform: translateY(0);}
        }
        @keyframes popIn {
            0% { transform: scale(0.7);}
            80% { transform: scale(1.1);}
            100% { transform: scale(1);}
        }
        .menu-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
            gap: 32px;
            justify-items: center;
            margin: 0 auto 0 auto;
            max-width: 700px;
            padding: 0 16px;
        }
        .menu-button {
            width: 140px;
            height: 140px;
            background: linear-gradient(135deg, #f5f7fa 0%, #e3e9f0 100%);
            color: #2d3748;
            border-radius: 24px;
            border: none;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            font-size: 16px;
            font-weight: 700;
            box-shadow: 0 4px 18px rgba(44, 62, 80, 0.10);
            cursor: pointer;
            transition: transform 0.18s cubic-bezier(.4,2,.6,1), box-shadow 0.18s, background 0.18s;
            position: relative;
            overflow: hidden;
            outline: none;
        }
        .menu-button:after {
            content: '';
            position: absolute;
            inset: 0;
            background: rgba(229,57,53,0.08);
            opacity: 0;
            transition: opacity 0.2s;
        }
        .menu-button:hover, .menu-button:focus {
            transform: translateY(-6px) scale(1.04);
            box-shadow: 0 10px 32px rgba(44, 62, 80, 0.16);
            background: linear-gradient(135deg, #e3e9f0 0%, #f5f7fa 100%);
        }
        .menu-button:active:after {
            opacity: 1;
        }
        .menu-button i {
            font-size: 38px;
            margin-bottom: 16px;
            color: #e53935;
            background: #fff;
            border-radius: 50%;
            padding: 14px;
            box-shadow: 0 2px 8px rgba(44, 62, 80, 0.10);
            transition: background 0.2s, color 0.2s;
        }
        .menu-button:hover i, .menu-button:focus i {
            background: #e53935;
            color: #fff;
        }
        .menu-button span {
            color: #2d3748;
            letter-spacing: 0.01em;
            font-size: 1.08rem;
        }
        .menu-button .badge {
            position: absolute;
            top: 14px;
            right: 18px;
            background: #e53935;
            color: #fff;
            font-size: 0.75rem;
            padding: 2px 8px;
            border-radius: 12px;
            font-weight: 600;
            box-shadow: 0 1px 4px rgba(229,57,53,0.13);
        }
        @media (max-width: 700px) {
            .menu-grid {
                grid-template-columns: repeat(auto-fit, minmax(110px, 1fr));
                gap: 18px;
                max-width: 100%;
            }
            .menu-button {
                width: 110px;
                height: 110px;
                font-size: 13px;
            }
            .menu-button i {
                font-size: 22px;
                padding: 8px;
            }
        }
    </style>

    <div class="fortigate-hero">
        <div class="fortigate-logo">
            <i class="bi bi-shield-lock-fill"></i>
        </div>
        <div class="fortigate-title">Fortigate</div>
        <div class="fortigate-desc">Dashboard monitoring &amp; management untuk perangkat Fortigate Anda.</div>
    </div>

    <div class="menu-grid">
        <a href="{{ route('admin.fortigate.port') }}" class="menu-button" title="Ethernet Port">
            <i class="bi bi-ethernet"></i>
            <span>Port</span>
        </a>
        <a class="menu-button" title="Device Info">
            <i class="bi bi-pc-display"></i>
            <span>Device</span>
            <span class="badge" style="display:none;">New</span>
        </a>
        <a href="{{ route('admin.fortigate.api') }}" class="menu-button" title="API Integration">
            <i class="bi bi-code"></i>
            <span>API</span>
        </a>
        <button class="menu-button" title="Virus Logs">
            <i class="bi bi-virus"></i>
            <span>Virus Logs</span>
            <span class="badge">Beta</span>
        </button>
    </div>

    <!-- Font Awesome CDN for icons -->
    <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
</div>