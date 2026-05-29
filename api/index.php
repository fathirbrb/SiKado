<?php
define("TMP", rtrim(sys_get_temp_dir(), "/"));
define("DB_FILE", TMP . "/dgsign_db.json");

require_once __DIR__ . "/../vendor/autoload.php";
use RobThree\Auth\TwoFactorAuth;
use RobThree\Auth\Providers\Qr\QRServerProvider;

$tfa = new TwoFactorAuth(new QRServerProvider());
$message = "";
$message_type = "";
$qr_image_base64 = "";

// ── JSON DB ──────────────────────────────────────
function db_all(): array
{
    if (!file_exists(DB_FILE)) {
        return [];
    }
    return json_decode(file_get_contents(DB_FILE), true) ?? [];
}

function db_get(string $nim): ?array
{
    return db_all()[$nim] ?? null;
}

function db_save(string $nim, array $data): void
{
    $db = db_all();
    $db[$nim] = $data;
    file_put_contents(DB_FILE, json_encode($db, JSON_PRETTY_PRINT));
}
// ─────────────────────────────────────────────────

function valid_nim(string $nim): bool
{
    return (bool) preg_match('/^[a-zA-Z0-9_-]{3,20}$/', $nim);
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $action = $_POST["action"] ?? "";

    if ($action === "setup_otp") {
        $username = trim($_POST["username"]);
        if (!valid_nim($username)) {
            $message =
                "NIM hanya boleh huruf, angka, underscore, atau dash (3–20 karakter).";
            $message_type = "error";
        } else {
            $secret = $tfa->createSecret();
            db_save($username, [
                "otp_secret" => $secret,
                "has_digital_id" => false,
                "has_signed" => false,
            ]);
            $qr_image_base64 = $tfa->getQRCodeImageAsDataUri(
                "STEI-ITB ($username)",
                $secret,
            );
            $message =
                "QR Code berhasil di-generate. Scan dengan aplikasi Authenticator.";
            $message_type = "success";
        }
    }

    if ($action === "request_id") {
        $username = trim($_POST["username"]);
        $otp = trim($_POST["otp"]);
        $password_p12 = $_POST["password"];
        $user = db_get($username);

        if (!valid_nim($username)) {
            $message = "NIM tidak valid.";
            $message_type = "error";
        } elseif (strlen($otp) !== 6 || !ctype_digit($otp)) {
            $message = "OTP harus 6 digit angka.";
            $message_type = "error";
        } elseif (strlen($password_p12) < 6) {
            $message = "Password minimal 6 karakter.";
            $message_type = "error";
        } elseif (!$user) {
            $message = "NIM '$username' belum terdaftar. Selesaikan Langkah 1 terlebih dahulu.";
            $message_type = "error";
        } elseif ($user["has_digital_id"]) {
            $message = "NIM '$username' sudah memiliki sertifikat digital.";
            $message_type = "warning";
        } else {
            if (!$tfa->verifyCode($user["otp_secret"], $otp)) {
                $message = "Kode OTP salah atau kedaluwarsa.";
                $message_type = "error";
            } else {
                $config = [
                    "private_key_bits" => 2048,
                    "private_key_type" => OPENSSL_KEYTYPE_RSA,
                ];
                $key = openssl_pkey_new($config);
                $dn = [
                    "commonName" => $username,
                    "organizationName" => "Mahasiswa STEI ITB",
                    "countryName" => "ID",
                ];
                $csr = openssl_csr_new($dn, $key);
                $cert = openssl_csr_sign($csr, null, $key, 365, $config);
                $filename = $username . "_identity.p12";

                if (
                    openssl_pkcs12_export_to_file(
                        $cert,
                        TMP . "/$filename",
                        $key,
                        $password_p12,
                    )
                ) {
                    $user["has_digital_id"] = true;
                    $user["p12_file"] = $filename;
                    db_save($username, $user);
                    $message = "Sertifikat digital <b>$filename</b> berhasil dibuat.";
                    $message_type = "success";
                } else {
                    $message =
                        "Gagal membuat file .p12. Pastikan folder memiliki permission write.";
                    $message_type = "error";
                }
            }
        }
    }

    if ($action === "sign_data") {
        $username = trim($_POST["username"]);
        $password = $_POST["password"];
        $user = db_get($username);

        if (!valid_nim($username)) {
            $message = "NIM tidak valid.";
            $message_type = "error";
        } elseif (!$user) {
            $message = "NIM '$username' belum terdaftar. Selesaikan Langkah 1 terlebih dahulu.";
            $message_type = "error";
        } elseif (!$user["has_digital_id"]) {
            $message = "NIM '$username' belum memiliki sertifikat. Selesaikan Langkah 2 terlebih dahulu.";
            $message_type = "error";
        } else {
            $p12_file = TMP . "/" . $username . "_identity.p12";
            if (!file_exists($p12_file)) {
                $message = "File .p12 tidak ditemukan. Mungkin sudah terhapus.";
                $message_type = "error";
            } else {
                $certs = [];
                if (
                    openssl_pkcs12_read(
                        file_get_contents($p12_file),
                        $certs,
                        $password,
                    )
                ) {
                    $data = "NIM: $username menyatakan dokumen ini sah.";
                    openssl_sign(
                        $data,
                        $signature,
                        $certs["pkey"],
                        OPENSSL_ALGO_SHA256,
                    );
                    file_put_contents(TMP . "/tanda_tangan.sig", $signature);
                    file_put_contents(TMP . "/dokumen_mentah.txt", $data);
                    $user["has_signed"] = true;
                    db_save($username, $user);
                    $message =
                        "Dokumen berhasil ditandatangani. Signature disimpan di <b>tanda_tangan.sig</b>.";
                    $message_type = "success";
                } else {
                    $message = "Password sertifikat salah.";
                    $message_type = "error";
                }
            }
        }
    }

    if ($action === "verify_data") {
        $username = trim($_POST["username"]);
        $user = db_get($username);

        if (!valid_nim($username)) {
            $message = "NIM tidak valid.";
            $message_type = "error";
        } elseif (!$user) {
            $message = "NIM '$username' belum terdaftar. Selesaikan Langkah 1 terlebih dahulu.";
            $message_type = "error";
        } elseif (!$user["has_digital_id"]) {
            $message = "NIM '$username' belum memiliki sertifikat. Selesaikan Langkah 2 terlebih dahulu.";
            $message_type = "error";
        } elseif (!$user["has_signed"]) {
            $message = "NIM '$username' belum menandatangani dokumen. Selesaikan Langkah 3 terlebih dahulu.";
            $message_type = "error";
        } else {
            $p12_file = TMP . "/" . $username . "_identity.p12";
            $certs = [];
            openssl_pkcs12_read(
                file_get_contents($p12_file),
                $certs,
                $_POST["password_cek"],
            );
            $result = openssl_verify(
                file_get_contents(TMP . "/dokumen_mentah.txt"),
                file_get_contents(TMP . "/tanda_tangan.sig"),
                $certs["cert"],
                OPENSSL_ALGO_SHA256,
            );
            if ($result === 1) {
                $message = "Dokumen 100% asli dan valid dari pemilik NIM <b>$username</b>.";
                $message_type = "success";
            } elseif ($result === 0) {
                $message =
                    "Tanda tangan tidak valid! Dokumen telah dimanipulasi.";
                $message_type = "error";
            } else {
                $message = "Terjadi kesalahan saat verifikasi.";
                $message_type = "warning";
            }
        }
    }
}

$nim_check = isset($_POST["username"]) ? trim($_POST["username"]) : "";
$user_data = db_get($nim_check);
$step1_done = $user_data !== null;
$step2_done = $step1_done && !empty($user_data["has_digital_id"]);
$step3_done = $step2_done && !empty($user_data["has_signed"]);

if (!$step1_done) {
    $active_step = 1;
} elseif (!$step2_done) {
    $active_step = 2;
} elseif (!$step3_done) {
    $active_step = 3;
} else {
    $active_step = 4;
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SiKado</title>
    <link rel="stylesheet" href="../style.css">
</head>

<body>
    <div class="container">
        <div class="header">
            <h1>SiKado</h1>
            <p>Sistem Tanda Tangan Digital &mdash; Keamanan Jaringan</p>
        </div>
        <div class="content">

            <!-- Tombol Buka Petunjuk -->
            <button type="button" class="btn-guide"
                onclick="document.getElementById('modal-guide').classList.add('open')">
                &#9432; Petunjuk Penggunaan
            </button>

            <!-- Modal Petunjuk -->
            <div id="modal-guide" class="modal-overlay" onclick="if(event.target===this)this.classList.remove('open')">
                <div class="modal">
                    <div class="modal-header">
                        <div>
                            <p class="modal-title">Petunjuk Penggunaan</p>
                            <p class="modal-sub">Baca sebelum memulai</p>
                        </div>
                        <button class="modal-close"
                            onclick="document.getElementById('modal-guide').classList.remove('open')">&times;</button>
                    </div>

                    <div class="modal-body">

                        <!-- Prasyarat -->
                        <div class="guide-prereq">
                            <div class="guide-prereq-text">
                                <p class="guide-prereq-title">&#128241; Download Google Authenticator Dulu</p>
                                <p class="guide-prereq-desc">Aplikasi ini wajib ada di smartphone kamu untuk
                                    menghasilkan kode OTP 6-digit saat verifikasi.</p>
                                <div class="guide-badges">
                                    <a href="https://play.google.com/store/apps/details?id=com.google.android.apps.authenticator2"
                                        target="_blank">
                                        <img src="https://upload.wikimedia.org/wikipedia/commons/7/78/Google_Play_Store_badge_EN.svg"
                                            alt="Google Play" height="34">
                                    </a>
                                    <a href="https://apps.apple.com/app/google-authenticator/id388497605"
                                        target="_blank">
                                        <img src="https://developer.apple.com/assets/elements/badges/download-on-the-app-store.svg"
                                            alt="App Store" height="34">
                                    </a>
                                </div>
                            </div>
                            <img src="https://www.gstatic.com/images/branding/product/2x/authenticator_96dp.png"
                                alt="Google Authenticator" class="guide-prereq-img">
                        </div>

                        <!-- Steps -->
                        <p class="guide-section-label">Langkah-langkah</p>

                        <div class="guide-flow">

                            <div class="guide-step">
                                <div class="guide-step-num">1</div>
                                <div class="guide-step-content">
                                    <p class="guide-step-title">Setup OTP</p>
                                    <p class="guide-step-desc">Masukkan NPM di form <strong>Langkah 1</strong>, lalu
                                        klik <em>Generate QR Code</em>. Scan QR Code yang muncul menggunakan Google
                                        Authenticator di smartphone kamu.</p>
                                    <div class="guide-tip">Gunakan NPM yang sama di semua langkah berikutnya.</div>
                                </div>
                            </div>

                            <div class="guide-connector"></div>

                            <div class="guide-step">
                                <div class="guide-step-num">2</div>
                                <div class="guide-step-content">
                                    <p class="guide-step-title">Ambil Digital ID <code>.p12</code></p>
                                    <p class="guide-step-desc">Buka Google Authenticator, masukkan kode 6-digit yang
                                        muncul beserta NPM dan buat password sertifikat minimal 6 karakter.</p>
                                    <div class="guide-tip">Kode OTP berubah setiap 30 detik &mdash; masukkan sebelum
                                        habis.</div>
                                </div>
                            </div>

                            <div class="guide-connector"></div>

                            <div class="guide-step">
                                <div class="guide-step-num">3</div>
                                <div class="guide-step-content">
                                    <p class="guide-step-title">Tanda Tangani Dokumen</p>
                                    <p class="guide-step-desc">Masukkan NPM dan password sertifikat. Sistem
                                        menandatangani dokumen secara kriptografis menggunakan private key kamu.</p>
                                    <div class="guide-tip">Hasil disimpan di <code>tanda_tangan.sig</code>.</div>
                                </div>
                            </div>

                            <div class="guide-connector"></div>

                            <div class="guide-step">
                                <div class="guide-step-num">4</div>
                                <div class="guide-step-content">
                                    <p class="guide-step-title">Verifikasi Dokumen</p>
                                    <p class="guide-step-desc">Masukkan NPM dan password sertifikat untuk membuktikan
                                        dokumen masih asli dan belum dimanipulasi.</p>
                                    <div class="guide-tip">Jika dokumen diubah setelah ditandatangani, verifikasi akan
                                        <strong>gagal</strong>.
                                    </div>
                                </div>
                            </div>

                        </div>

                        <p class="guide-note">&#9888; Setiap langkah harus diselesaikan secara berurutan. Sistem akan
                            menolak jika kamu melompati langkah.</p>

                    </div>
                </div>
            </div>

            <!-- Step Indicator -->
            <div class="stepper">
                <div class="step <?php echo $step1_done
                    ? "done"
                    : ($active_step === 1
                        ? "active"
                        : ""); ?>">
                    <div class="step-circle"><?php echo $step1_done
                        ? "✓"
                        : "1"; ?></div>
                    <div class="step-label">Setup OTP</div>
                </div>
                <div class="step-line <?php echo $step1_done
                    ? "done"
                    : ""; ?>"></div>
                <div class="step <?php echo $step2_done
                    ? "done"
                    : ($active_step === 2
                        ? "active"
                        : ""); ?>">
                    <div class="step-circle"><?php echo $step2_done
                        ? "✓"
                        : "2"; ?></div>
                    <div class="step-label">Digital ID</div>
                </div>
                <div class="step-line <?php echo $step2_done
                    ? "done"
                    : ""; ?>"></div>
                <div class="step <?php echo $step3_done
                    ? "done"
                    : ($active_step === 3
                        ? "active"
                        : ""); ?>">
                    <div class="step-circle"><?php echo $step3_done
                        ? "✓"
                        : "3"; ?></div>
                    <div class="step-label">Sign Data</div>
                </div>
                <div class="step-line <?php echo $step3_done
                    ? "done"
                    : ""; ?>"></div>
                <div class="step <?php echo $active_step === 4
                    ? "active"
                    : ""; ?>">
                    <div class="step-circle">4</div>
                    <div class="step-label">Verifikasi</div>
                </div>
            </div>

            <!-- Pesan Notifikasi -->
            <?php if (!empty($message)): ?>
                <div class="message <?php echo $message_type; ?>"><?php echo $message; ?></div>
            <?php endif; ?>

            <!-- Langkah 1 -->
            <div class="step-card">
                <h3><span class="step-number">1</span> Setup OTP</h3>
                <p class="helper-text">Generate QR Code untuk autentikasi 2-faktor.</p>
                <form method="POST">
                    <input type="hidden" name="action" value="setup_otp">
                    <div class="form-group">
                        <label for="u1">NPM</label>
                        <input type="text" id="u1" name="username" placeholder="Masukkan NPM" required>
                    </div>
                    <button type="submit">Generate QR Code</button>
                </form>
                <?php if (!empty($qr_image_base64)): ?>
                    <div class="qr-container">
                        <p>Scan dengan Authenticator App</p>
                        <img src="<?php echo $qr_image_base64; ?>" width="200" alt="QR Code">
                        <span>Google / Microsoft Authenticator</span>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Langkah 2 -->
            <div class="step-card">
                <h3><span class="step-number">2</span> Ambil Digital ID</h3>
                <p class="helper-text">Verifikasi OTP untuk mendapatkan sertifikat digital.</p>
                <form method="POST">
                    <input type="hidden" name="action" value="request_id">
                    <div class="form-group">
                        <label for="u2">NPM</label>
                        <input type="text" id="u2" name="username" placeholder="Masukkan NPM" required>
                    </div>
                    <div class="form-group">
                        <label for="otp">Kode OTP</label>
                        <input type="text" id="otp" name="otp" placeholder="6 digit kode OTP" required maxlength="6">
                    </div>
                    <div class="form-group">
                        <label for="p1">Password Sertifikat</label>
                        <input type="password" id="p1" name="password" placeholder="Min. 6 karakter" required>
                    </div>
                    <button type="submit">Terbitkan Sertifikat</button>
                </form>
            </div>

            <!-- Langkah 3 -->
            <div class="step-card">
                <h3><span class="step-number">3</span> Tanda Tangani Dokumen</h3>
                <p class="helper-text">Tanda tangani dokumen menggunakan sertifikat digital.</p>
                <form method="POST">
                    <input type="hidden" name="action" value="sign_data">
                    <div class="form-group">
                        <label for="u3">NPM</label>
                        <input type="text" id="u3" name="username" placeholder="Masukkan NPM" required>
                    </div>
                    <div class="form-group">
                        <label for="p2">Password Sertifikat</label>
                        <input type="password" id="p2" name="password" placeholder="Password sertifikat" required>
                    </div>
                    <button type="submit">Tanda Tangani</button>
                </form>
            </div>

            <!-- Langkah 4 -->
            <div class="step-card">
                <h3><span class="step-number">4</span> Verifikasi Dokumen</h3>
                <p class="helper-text">Verifikasi keaslian dan integritas dokumen yang ditandatangani.</p>
                <form method="POST">
                    <input type="hidden" name="action" value="verify_data">
                    <div class="form-group">
                        <label for="u4">NIM</label>
                        <input type="text" id="u4" name="username" placeholder="Masukkan NPM" required>
                    </div>
                    <div class="form-group">
                        <label for="p3">Password Sertifikat</label>
                        <input type="password" id="p3" name="password_cek" placeholder="Password sertifikat" required>
                    </div>
                    <button type="submit">Verifikasi</button>
                </form>
            </div>

        </div>
    </div>
</body>

</html>
