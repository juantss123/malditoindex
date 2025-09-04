<?php
session_start();
require_once 'config/database.php';

// Check authentication
requireLogin();

// Get plan from URL
$plan = $_GET['plan'] ?? '';
$validPlans = ['start', 'clinic', 'enterprise'];

if (!in_array($plan, $validPlans)) {
    header('Location: dashboard.php');
    exit();
}

// Get user data
$database = new Database();
$db = $database->getConnection();

try {
    $stmt = $db->prepare("
        SELECT first_name, last_name, email, clinic_name
        FROM user_profiles 
        WHERE user_id = ?
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $userProfile = $stmt->fetch();
    
    // Get payment settings
    $stmt = $db->prepare("SELECT * FROM payment_settings WHERE id = 1");
    $stmt->execute();
    $paymentSettings = $stmt->fetch();
    
} catch (Exception $e) {
    $userProfile = null;
    $paymentSettings = null;
}

// Plan details
// Get plan details from database
$planDetails = null;
try {
    $stmt = $db->prepare("SELECT * FROM subscription_plans WHERE plan_type = ?");
    $stmt->execute([$plan]);
    $planData = $stmt->fetch();
    
    if ($planData) {
        $planDetails = [
            'name' => $planData['name'],
            'price' => $planData['price_monthly'],
            'features' => json_decode($planData['features'], true)
        ];
    }
} catch (Exception $e) {
    // Fallback to default if database fails
}

// Fallback plan details if not found in database
if (!$planDetails) {
    $fallbackPlans = [
        'start' => [
            'name' => 'Start',
            'price' => 14999.00,
            'features' => ['1 profesional', 'Agenda & turnos', 'Historia clínica', 'Recordatorios']
        ],
        'clinic' => [
            'name' => 'Clinic',
            'price' => 24999.00,
            'features' => ['Hasta 3 profesionales', 'Portal del paciente', 'Facturación', 'Reportes avanzados']
        ],
        'enterprise' => [
            'name' => 'Enterprise',
            'price' => 49999.00,
            'features' => ['Profesionales ilimitados', 'Integraciones', 'Soporte prioritario', 'Entrenamiento']
        ]
    ];
    $planDetails = $fallbackPlans[$plan];
}

$selectedPlan = $planDetails;
?>
<!doctype html>
<html lang="es" class="h-100">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Pago - Plan <?php echo $selectedPlan['name']; ?> - DentexaPro</title>
  <meta name="description" content="Completa tu suscripción a DentexaPro">
  <meta name="theme-color" content="#2F96EE" />
  <link rel="icon" href="assets/img/favicon.svg" type="image/svg+xml" />

  <!-- Google Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">

  <!-- Bootstrap 5.3 + Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

  <!-- AOS (Animate On Scroll) -->
  <link href="https://unpkg.com/aos@2.3.4/dist/aos.css" rel="stylesheet">

  <!-- App styles -->
  <link href="assets/css/styles.css" rel="stylesheet">
</head>
<body class="bg-dark-ink text-body min-vh-100">
  <!-- Decorative animated blobs -->
  <div class="bg-blobs" aria-hidden="true">
    <span></span><span></span><span></span>
  </div>

  <!-- Nav -->
  <nav class="navbar navbar-expand-lg navbar-dark sticky-top glass-nav">
    <div class="container-fluid">
      <a class="navbar-brand d-flex align-items-center gap-2" href="dashboard.php">
        <img src="assets/img/logo.svg" width="28" height="28" alt="DentexaPro logo" />
        <strong>DentexaPro</strong>
      </a>
      <div class="ms-auto d-flex align-items-center gap-3">
        <span class="text-light small"><?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
        <a href="dashboard.php" class="btn btn-outline-light">
          <i class="bi bi-arrow-left me-2"></i>Volver al dashboard
        </a>
      </div>
    </div>
  </nav>

  <!-- Payment Page -->
  <main class="section-pt pb-5">
    <div class="container">
      <div class="row justify-content-center">
        <div class="col-lg-8 col-xl-6">
          <!-- Header -->
          <div class="text-center mb-5" data-aos="fade-down" data-aos-duration="800">
            <h1 class="text-white mb-3">
              <i class="bi bi-credit-card me-2"></i>Completar suscripción
            </h1>
            <p class="text-light opacity-85">
              Elegiste el plan <strong><?php echo $selectedPlan['name']; ?></strong>. Selecciona tu método de pago preferido.
            </p>
          </div>

          <!-- Plan Summary -->
          <div class="glass-card p-4 mb-4" data-aos="zoom-in" data-aos-duration="800" data-aos-delay="200">
            <div class="row align-items-center">
              <div class="col-md-8">
                <h3 class="text-white mb-2">
                  <i class="bi bi-star me-2"></i>Plan <?php echo $selectedPlan['name']; ?>
                </h3>
                <ul class="list-unstyled mb-0">
                  <?php foreach ($selectedPlan['features'] as $feature): ?>
                  <li class="text-light opacity-85 mb-1">
                    <i class="bi bi-check-circle text-success me-2"></i><?php echo $feature; ?>
                  </li>
                  <?php endforeach; ?>
                </ul>
              </div>
              <div class="col-md-4 text-md-end mt-3 mt-md-0">
                <div class="display-6 fw-bold text-white">
                  $<?php echo number_format($selectedPlan['price'], 0, ',', '.'); ?>
                </div>
                <small class="text-light opacity-75">ARS por mes</small>
              </div>
            </div>
          </div>

          <!-- Payment Methods -->
          <div class="glass-card p-4" data-aos="fade-up" data-aos-duration="800" data-aos-delay="400">
            <h3 class="text-white mb-4">
              <i class="bi bi-wallet2 me-2"></i>Método de pago
            </h3>

            <!-- Payment Method Selection -->
            <div class="row g-3 mb-4">
              <div class="col-md-6">
                <input type="radio" class="btn-check" name="paymentMethod" id="mercadopago" value="mercadopago" checked>
                <label class="btn btn-outline-light w-100 p-3" for="mercadopago">
                  <div class="d-flex align-items-center">
                    <div class="bg-primary rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px;">
                      <i class="bi bi-credit-card text-white"></i>
                    </div>
                    <div class="text-start">
                      <div class="fw-bold">MercadoPago</div>
                      <small class="opacity-75">Tarjeta de crédito/débito</small>
                    </div>
                  </div>
                </label>
              </div>
              <div class="col-md-6">
                <input type="radio" class="btn-check" name="paymentMethod" id="transfer" value="transfer">
                <label class="btn btn-outline-light w-100 p-3" for="transfer">
                  <div class="d-flex align-items-center">
                    <div class="bg-info rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px;">
                      <i class="bi bi-bank text-white"></i>
                    </div>
                    <div class="text-start">
                      <div class="fw-bold">Transferencia</div>
                      <small class="opacity-75">Transferencia bancaria</small>
                    </div>
                  </div>
                </label>
              </div>
            </div>

            <!-- MercadoPago Section -->
            <div id="mercadopagoSection" class="payment-section">
              <?php if ($paymentSettings && $paymentSettings['mercadopago_enabled']): ?>
              <div class="glass-card p-4">
                <h5 class="text-white mb-3">
                  <i class="bi bi-credit-card me-2"></i>Pago con MercadoPago
                </h5>
                <p class="text-light opacity-85 mb-4">
                  Paga de forma segura con tu tarjeta de crédito o débito a través de MercadoPago.
                </p>
                <button class="btn btn-primary btn-lg w-100" onclick="processMercadoPago()">
                  <i class="bi bi-shield-check me-2"></i>Pagar con MercadoPago
                </button>
              </div>
              <?php else: ?>
              <div class="glass-card p-4 border-warning">
                <div class="text-center">
                  <i class="bi bi-exclamation-triangle text-warning fs-1 mb-3"></i>
                  <h5 class="text-warning mb-2">MercadoPago no configurado</h5>
                  <p class="text-light opacity-85 mb-0">
                    El administrador debe configurar MercadoPago desde el panel de configuración.
                  </p>
                </div>
              </div>
              <?php endif; ?>
            </div>

            <!-- Bank Transfer Section -->
            <div id="transferSection" class="payment-section" style="display: none;">
              <?php if ($paymentSettings && $paymentSettings['bank_transfer_enabled']): ?>
              <div class="glass-card p-4">
                <h5 class="text-white mb-3">
                  <i class="bi bi-bank me-2"></i>Transferencia bancaria
                </h5>
                <p class="text-light opacity-85 mb-4">
                  Realiza una transferencia a nuestra cuenta bancaria y envíanos el comprobante.
                </p>
                
                <div class="row g-3 mb-4">
                  <div class="col-md-6">
                    <div class="glass-card p-3">
                      <div class="d-flex align-items-center mb-2">
                        <i class="bi bi-building text-primary me-2"></i>
                        <strong class="text-white">Banco</strong>
                      </div>
                      <div class="text-light"><?php echo htmlspecialchars($paymentSettings['bank_name']); ?></div>
                    </div>
                  </div>
                  <div class="col-md-6">
                    <div class="glass-card p-3">
                      <div class="d-flex align-items-center mb-2">
                        <i class="bi bi-person text-info me-2"></i>
                        <strong class="text-white">Titular</strong>
                      </div>
                      <div class="text-light"><?php echo htmlspecialchars($paymentSettings['account_holder']); ?></div>
                    </div>
                  </div>
                  <div class="col-md-6">
                    <div class="glass-card p-3">
                      <div class="d-flex align-items-center mb-2">
                        <i class="bi bi-hash text-warning me-2"></i>
                        <strong class="text-white">CBU/CVU</strong>
                      </div>
                      <div class="text-white font-monospace"><?php echo htmlspecialchars($paymentSettings['cbu_cvu']); ?></div>
                      <button class="btn btn-sm btn-outline-primary mt-2" onclick="copyToClipboard('<?php echo htmlspecialchars($paymentSettings['cbu_cvu']); ?>')">
                        <i class="bi bi-clipboard me-1"></i>Copiar
                      </button>
                    </div>
                  </div>
                  <div class="col-md-6">
                    <div class="glass-card p-3">
                      <div class="d-flex align-items-center mb-2">
                        <i class="bi bi-currency-dollar text-success me-2"></i>
                        <strong class="text-white">Monto</strong>
                      </div>
                      <div class="text-success fw-bold fs-5">$<?php echo number_format($selectedPlan['price'], 0, ',', '.'); ?> ARS</div>
                    </div>
                  </div>
                </div>

                <div class="mb-4">
                  <label class="form-label text-light">Subir comprobante de transferencia</label>
                  <input type="file" class="form-control glass-input" accept="image/*,.pdf" id="transferProof">
                  <small class="text-light opacity-75">Formatos aceptados: JPG, PNG, PDF</small>
                </div>

                <button class="btn btn-info btn-lg w-100" onclick="processTransfer()">
                  <i class="bi bi-upload me-2"></i>Enviar comprobante
                </button>
              </div>
              <?php else: ?>
              <div class="glass-card p-4 border-warning">
                <div class="text-center">
                  <i class="bi bi-exclamation-triangle text-warning fs-1 mb-3"></i>
                  <h5 class="text-warning mb-2">Transferencia bancaria no configurada</h5>
                  <p class="text-light opacity-85 mb-0">
                    El administrador debe configurar los datos bancarios desde el panel de configuración.
                  </p>
                </div>
              </div>
              <?php endif; ?>
            </div>

            <!-- Security Notice -->
            <div class="text-center mt-4">
              <div class="glass-card p-3">
                <p class="text-light opacity-75 mb-0 small">
                  <i class="bi bi-shield-check text-success me-2"></i>
                  Todos los pagos son procesados de forma segura. Tus datos están protegidos con cifrado SSL.
                </p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </main>

  <!-- Success Modal -->
  <div class="modal fade" id="successModal" tabindex="-1" aria-labelledby="successModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content bg-dark border-0">
        <div class="modal-header border-bottom border-secondary">
          <h5 class="modal-title text-white" id="successModalLabel">
            <i class="bi bi-check-circle text-success me-2"></i>¡Pago procesado!
          </h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body p-4 text-center">
          <div class="mb-4">
            <i class="bi bi-check-circle-fill text-success" style="font-size: 4rem;"></i>
          </div>
          <h4 class="text-white mb-3">¡Gracias por tu suscripción!</h4>
          <p class="text-light opacity-85 mb-4" id="successMessage">
            Tu pago ha sido procesado exitosamente. Tu plan estará activo en unos minutos.
          </p>
          <button class="btn btn-primary btn-lg" onclick="window.location.href='dashboard.php'">
            <i class="bi bi-house me-2"></i>Volver al dashboard
          </button>
        </div>
      </div>
    </div>
  </div>

  <!-- Scripts -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://unpkg.com/aos@2.3.4/dist/aos.js"></script>
  <script>
    // Init AOS
    if (window.AOS) {
      AOS.init({
        duration: 1000,
        once: true,
        offset: 100,
        easing: 'ease-out-quart'
      });
    }

    // Payment method toggle
    const paymentMethods = document.querySelectorAll('input[name="paymentMethod"]');
    const mercadopagoSection = document.getElementById('mercadopagoSection');
    const transferSection = document.getElementById('transferSection');

    paymentMethods.forEach(method => {
      method.addEventListener('change', (e) => {
        if (e.target.value === 'mercadopago') {
          mercadopagoSection.style.display = 'block';
          transferSection.style.display = 'none';
        } else {
          mercadopagoSection.style.display = 'none';
          transferSection.style.display = 'block';
        }
      });
    });

    // Copy to clipboard function
    function copyToClipboard(text) {
      navigator.clipboard.writeText(text).then(() => {
        // Show temporary success message
        const toast = document.createElement('div');
        toast.className = 'position-fixed top-0 end-0 m-3 alert alert-success glass-card';
        toast.style.zIndex = '9999';
        toast.innerHTML = '<i class="bi bi-check-circle me-2"></i>CBU copiado al portapapeles';
        document.body.appendChild(toast);
        
        setTimeout(() => {
          toast.remove();
        }, 2000);
      }).catch(err => {
        console.error('Error copying to clipboard:', err);
      });
    }

    // Process MercadoPago payment
    function processMercadoPago() {
      // Show loading
      const btn = event.target;
      const originalText = btn.innerHTML;
      btn.disabled = true;
      btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Procesando...';

      // Simulate MercadoPago integration
      setTimeout(() => {
        // In a real implementation, this would integrate with MercadoPago API
        document.getElementById('successMessage').textContent = 
          'Tu pago con MercadoPago ha sido procesado exitosamente. Tu plan <?php echo $selectedPlan['name']; ?> estará activo en unos minutos.';
        
        const modal = new bootstrap.Modal(document.getElementById('successModal'));
        modal.show();
        
        btn.disabled = false;
        btn.innerHTML = originalText;
      }, 2000);
    }

    // Process bank transfer
    function processTransfer() {
      const fileInput = document.getElementById('transferProof');
      
      if (!fileInput.files.length) {
        alert('Por favor, sube el comprobante de transferencia');
        return;
      }

      // Show loading
      const btn = event.target;
      const originalText = btn.innerHTML;
      btn.disabled = true;
      btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Enviando...';

      // Create FormData for file upload
      const formData = new FormData();
      formData.append('transfer_proof', fileInput.files[0]);
      formData.append('plan_type', '<?php echo $plan; ?>');
      formData.append('amount', '<?php echo $selectedPlan['price']; ?>');

      // Send to server
      fetch('api/upload-transfer-proof.php', {
        method: 'POST',
        body: formData
      })
      .then(response => response.json())
      .then(data => {
        if (data.error) {
          alert('Error: ' + data.error);
        } else {
          document.getElementById('successMessage').textContent = data.message;
          const modal = new bootstrap.Modal(document.getElementById('successModal'));
          modal.show();
        }
      })
      .catch(error => {
        console.error('Error uploading transfer proof:', error);
        alert('Error al enviar el comprobante. Por favor, intentá nuevamente.');
      })
      .finally(() => {
        btn.disabled = false;
        btn.innerHTML = originalText;
      });
    }
  </script>
</body>
</html>