<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit;

$dbFile = __DIR__ . '/hospital_billing.sqlite';

function db() {
    global $dbFile;
    $pdo = new PDO('sqlite:' . $dbFile);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec("CREATE TABLE IF NOT EXISTS users (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT NOT NULL, email TEXT NOT NULL UNIQUE, password TEXT NOT NULL, role TEXT NOT NULL, status TEXT NOT NULL DEFAULT 'Active', created_at TEXT NOT NULL)");
    $pdo->exec("CREATE TABLE IF NOT EXISTS patients (id INTEGER PRIMARY KEY AUTOINCREMENT, patient_no TEXT NOT NULL UNIQUE, full_name TEXT NOT NULL, gender TEXT NOT NULL, birth_date TEXT, contact TEXT, address TEXT, emergency_contact TEXT, status TEXT NOT NULL DEFAULT 'Active', created_at TEXT NOT NULL)");
    $pdo->exec("CREATE TABLE IF NOT EXISTS doctors (id INTEGER PRIMARY KEY AUTOINCREMENT, full_name TEXT NOT NULL, specialization TEXT NOT NULL, contact TEXT, room_no TEXT, status TEXT NOT NULL DEFAULT 'Active', created_at TEXT NOT NULL)");
    $pdo->exec("CREATE TABLE IF NOT EXISTS appointments (id INTEGER PRIMARY KEY AUTOINCREMENT, patient_id INTEGER NOT NULL, doctor_id INTEGER NOT NULL, appointment_date TEXT NOT NULL, appointment_time TEXT NOT NULL, reason TEXT, status TEXT NOT NULL DEFAULT 'Scheduled', created_at TEXT NOT NULL)");
    $pdo->exec("CREATE TABLE IF NOT EXISTS admissions (id INTEGER PRIMARY KEY AUTOINCREMENT, patient_id INTEGER NOT NULL, room_no TEXT NOT NULL, admission_date TEXT NOT NULL, discharge_date TEXT, diagnosis TEXT, status TEXT NOT NULL DEFAULT 'Admitted', created_at TEXT NOT NULL)");
    $pdo->exec("CREATE TABLE IF NOT EXISTS services (id INTEGER PRIMARY KEY AUTOINCREMENT, service_code TEXT NOT NULL UNIQUE, service_name TEXT NOT NULL, category TEXT NOT NULL, price REAL NOT NULL DEFAULT 0, status TEXT NOT NULL DEFAULT 'Active', created_at TEXT NOT NULL)");
    $pdo->exec("CREATE TABLE IF NOT EXISTS invoices (id INTEGER PRIMARY KEY AUTOINCREMENT, invoice_no TEXT NOT NULL UNIQUE, patient_id INTEGER NOT NULL, invoice_date TEXT NOT NULL, subtotal REAL NOT NULL DEFAULT 0, discount REAL NOT NULL DEFAULT 0, tax_rate REAL NOT NULL DEFAULT 0, tax_amount REAL NOT NULL DEFAULT 0, total_amount REAL NOT NULL DEFAULT 0, amount_paid REAL NOT NULL DEFAULT 0, balance REAL NOT NULL DEFAULT 0, payment_status TEXT NOT NULL DEFAULT 'Unpaid', notes TEXT, created_by INTEGER, created_at TEXT NOT NULL)");
    $pdo->exec("CREATE TABLE IF NOT EXISTS invoice_items (id INTEGER PRIMARY KEY AUTOINCREMENT, invoice_id INTEGER NOT NULL, service_id INTEGER, description TEXT NOT NULL, quantity INTEGER NOT NULL DEFAULT 1, unit_price REAL NOT NULL DEFAULT 0, line_total REAL NOT NULL DEFAULT 0)");
    seed($pdo);
    return $pdo;
}

function seed($pdo) {
    if ((int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn() === 0) {
        $s = $pdo->prepare("INSERT INTO users(name,email,password,role,status,created_at) VALUES(?,?,?,?,?,?)");
        $s->execute(['Hospital Administrator','admin@hospital.test','admin123','admin','Active',date('Y-m-d H:i:s')]);
        $s->execute(['Billing Officer','billing@hospital.test','billing123','billing','Active',date('Y-m-d H:i:s')]);
    }
    if ((int)$pdo->query("SELECT COUNT(*) FROM patients")->fetchColumn() === 0) {
        $s = $pdo->prepare("INSERT INTO patients(patient_no,full_name,gender,birth_date,contact,address,emergency_contact,status,created_at) VALUES(?,?,?,?,?,?,?,?,?)");
        $s->execute(['P-1001','Maria Santos','Female','1995-04-12','09171234567','Quezon City','Ana Santos - 09181234567','Active',date('Y-m-d H:i:s')]);
        $s->execute(['P-1002','Jose Reyes','Male','1988-11-22','09221234567','Manila City','Carlo Reyes - 09231234567','Active',date('Y-m-d H:i:s')]);
        $s->execute(['P-1003','Lina Cruz','Female','2001-07-08','09351234567','Pasig City','Nora Cruz - 09361234567','Active',date('Y-m-d H:i:s')]);
    }
    if ((int)$pdo->query("SELECT COUNT(*) FROM doctors")->fetchColumn() === 0) {
        $s = $pdo->prepare("INSERT INTO doctors(full_name,specialization,contact,room_no,status,created_at) VALUES(?,?,?,?,?,?)");
        $s->execute(['Dr. Adrian Lim','Internal Medicine','09190000001','302','Active',date('Y-m-d H:i:s')]);
        $s->execute(['Dr. Camille Torres','Pediatrics','09190000002','210','Active',date('Y-m-d H:i:s')]);
        $s->execute(['Dr. Ramon Villanueva','Cardiology','09190000003','415','Active',date('Y-m-d H:i:s')]);
    }
    if ((int)$pdo->query("SELECT COUNT(*) FROM services")->fetchColumn() === 0) {
        $s = $pdo->prepare("INSERT INTO services(service_code,service_name,category,price,status,created_at) VALUES(?,?,?,?,?,?)");
        $s->execute(['CONS-001','Doctor Consultation','Consultation',650,'Active',date('Y-m-d H:i:s')]);
        $s->execute(['LAB-101','Complete Blood Count','Laboratory',450,'Active',date('Y-m-d H:i:s')]);
        $s->execute(['IMG-201','Chest X-Ray','Imaging',900,'Active',date('Y-m-d H:i:s')]);
        $s->execute(['ROOM-301','Private Room Daily Rate','Room',2500,'Active',date('Y-m-d H:i:s')]);
        $s->execute(['MED-401','Medicine Package','Pharmacy',1200,'Active',date('Y-m-d H:i:s')]);
    }
}

function ok($data = []) { echo json_encode(['success' => true] + $data); exit; }
function fail($message, $code = 400) { http_response_code($code); echo json_encode(['success' => false, 'message' => $message]); exit; }
function next_invoice_no($pdo) { $count = (int)$pdo->query("SELECT COUNT(*) FROM invoices")->fetchColumn(); return 'INV-' . date('Ymd') . '-' . str_pad((string)($count + 1), 4, '0', STR_PAD_LEFT); }

$pdo = db();
$action = $_GET['action'] ?? $_POST['action'] ?? 'ping';

try {
    if ($action === 'ping') ok(['message' => 'Hospital Billing API is running']);
    if ($action === 'login') {
        $email = strtolower(trim($_POST['email'] ?? ''));
        $password = trim($_POST['password'] ?? '');
        $role = trim($_POST['role'] ?? '');
        if ($email === '' || $password === '' || $role === '') fail('Email, password, and role are required.');
        $s = $pdo->prepare("SELECT * FROM users WHERE email=? AND password=? AND role=? AND status='Active' LIMIT 1");
        $s->execute([$email,$password,$role]);
        $user = $s->fetch(PDO::FETCH_ASSOC);
        if (!$user) fail('Invalid login details.');
        ok(['message' => 'Login successful.', 'user' => $user]);
    }
    if ($action === 'list_patients') ok(['patients' => $pdo->query("SELECT * FROM patients ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC)]);
    if ($action === 'list_active_patients') { $s=$pdo->prepare("SELECT * FROM patients WHERE status='Active' ORDER BY full_name"); $s->execute(); ok(['patients'=>$s->fetchAll(PDO::FETCH_ASSOC)]); }
    if ($action === 'list_doctors') ok(['doctors' => $pdo->query("SELECT * FROM doctors ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC)]);
    if ($action === 'list_services') ok(['services' => $pdo->query("SELECT * FROM services ORDER BY category, service_name")->fetchAll(PDO::FETCH_ASSOC)]);
    if ($action === 'list_active_services') { $s=$pdo->prepare("SELECT * FROM services WHERE status='Active' ORDER BY category, service_name"); $s->execute(); ok(['services'=>$s->fetchAll(PDO::FETCH_ASSOC)]); }
    if ($action === 'list_invoices') {
        $rows = $pdo->query("SELECT invoices.*, patients.patient_no, patients.full_name AS patient_name, patients.contact FROM invoices JOIN patients ON patients.id=invoices.patient_id ORDER BY invoices.id DESC")->fetchAll(PDO::FETCH_ASSOC);
        ok(['invoices' => $rows]);
    }
    if ($action === 'get_invoice') {
        $id = (int)($_GET['id'] ?? 0); if ($id <= 0) fail('Invalid invoice ID.');
        $s = $pdo->prepare("SELECT invoices.*, patients.patient_no, patients.full_name AS patient_name, patients.contact, patients.address FROM invoices JOIN patients ON patients.id=invoices.patient_id WHERE invoices.id=? LIMIT 1");
        $s->execute([$id]); $invoice = $s->fetch(PDO::FETCH_ASSOC); if (!$invoice) fail('Invoice not found.');
        $s = $pdo->prepare("SELECT * FROM invoice_items WHERE invoice_id=? ORDER BY id ASC"); $s->execute([$id]);
        ok(['invoice'=>$invoice,'items'=>$s->fetchAll(PDO::FETCH_ASSOC)]);
    }
    if ($action === 'create_invoice') {
        $patientId=(int)($_POST['patient_id']??0); $createdBy=(int)($_POST['created_by']??0); $discount=(float)($_POST['discount']??0); $taxRate=(float)($_POST['tax_rate']??0); $amountPaid=(float)($_POST['amount_paid']??0); $paymentStatus=trim($_POST['payment_status']??'Unpaid'); $notes=trim($_POST['notes']??''); $items=json_decode($_POST['items']??'[]', true);
        if ($patientId<=0) fail('Please select a patient.');
        if (!in_array($paymentStatus, ['Unpaid','Partial','Paid'], true)) fail('Invalid payment status.');
        if (!is_array($items) || count($items)===0) fail('Please add at least one invoice item.');
        $s=$pdo->prepare("SELECT id FROM patients WHERE id=? LIMIT 1"); $s->execute([$patientId]); if(!$s->fetch()) fail('Patient not found.');
        $pdo->beginTransaction();
        $subtotal=0; $clean=[];
        foreach($items as $item){
            $description=trim($item['description']??''); $serviceId=(int)($item['service_id']??0); $quantity=max(1,(int)($item['quantity']??1)); $unitPrice=(float)($item['unit_price']??0);
            if($description==='' || $unitPrice<0){ $pdo->rollBack(); fail('Invalid invoice item details.'); }
            $lineTotal=$quantity*$unitPrice; $subtotal+=$lineTotal; $clean[]=['service_id'=>$serviceId,'description'=>$description,'quantity'=>$quantity,'unit_price'=>$unitPrice,'line_total'=>$lineTotal];
        }
        if($discount<0)$discount=0; if($discount>$subtotal)$discount=$subtotal; $taxAmount=max(0,($subtotal-$discount)*($taxRate/100)); $totalAmount=($subtotal-$discount)+$taxAmount;
        if($amountPaid<0)$amountPaid=0; if($amountPaid>$totalAmount)$amountPaid=$totalAmount; $balance=$totalAmount-$amountPaid;
        if($balance<=0){$paymentStatus='Paid';$balance=0;} elseif($amountPaid>0){$paymentStatus='Partial';} else {$paymentStatus='Unpaid';}
        $invoiceNo=next_invoice_no($pdo);
        $s=$pdo->prepare("INSERT INTO invoices(invoice_no,patient_id,invoice_date,subtotal,discount,tax_rate,tax_amount,total_amount,amount_paid,balance,payment_status,notes,created_by,created_at) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
        $s->execute([$invoiceNo,$patientId,date('Y-m-d'),$subtotal,$discount,$taxRate,$taxAmount,$totalAmount,$amountPaid,$balance,$paymentStatus,$notes,$createdBy,date('Y-m-d H:i:s')]);
        $invoiceId=(int)$pdo->lastInsertId(); $s=$pdo->prepare("INSERT INTO invoice_items(invoice_id,service_id,description,quantity,unit_price,line_total) VALUES(?,?,?,?,?,?)");
        foreach($clean as $item){$s->execute([$invoiceId,$item['service_id']>0?$item['service_id']:null,$item['description'],$item['quantity'],$item['unit_price'],$item['line_total']]);}
        $pdo->commit(); ok(['message'=>'Invoice saved successfully.','invoice_id'=>$invoiceId,'invoice_no'=>$invoiceNo]);
    }
    if ($action === 'update_invoice_payment') {
        $id=(int)($_POST['id']??0); $amountPaid=(float)($_POST['amount_paid']??0); if($id<=0) fail('Invalid invoice ID.');
        $s=$pdo->prepare("SELECT total_amount FROM invoices WHERE id=? LIMIT 1"); $s->execute([$id]); $invoice=$s->fetch(PDO::FETCH_ASSOC); if(!$invoice) fail('Invoice not found.');
        $total=(float)$invoice['total_amount']; if($amountPaid<0)$amountPaid=0; if($amountPaid>$total)$amountPaid=$total; $balance=$total-$amountPaid;
        $status=$balance<=0?'Paid':($amountPaid>0?'Partial':'Unpaid');
        $s=$pdo->prepare("UPDATE invoices SET amount_paid=?, balance=?, payment_status=? WHERE id=?"); $s->execute([$amountPaid,$balance,$status,$id]); ok(['message'=>'Payment updated.']);
    }
    fail('Invalid action.');
} catch(Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    fail($e->getMessage(), 500);
}
?>
