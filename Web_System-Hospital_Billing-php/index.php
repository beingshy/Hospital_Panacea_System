<?php
// hospital admin dashboard with sidebar navigation
$_SESSION['user'] = $user;
session_start();
$dbFile = __DIR__ . '/hospital_billing.sqlite';
function db(){
 global $dbFile; $pdo=new PDO('sqlite:'.$dbFile); $pdo->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);
 $pdo->exec("CREATE TABLE IF NOT EXISTS users (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT NOT NULL, email TEXT NOT NULL UNIQUE, password TEXT NOT NULL, role TEXT NOT NULL, status TEXT NOT NULL DEFAULT 'Active', created_at TEXT NOT NULL)");
 $pdo->exec("CREATE TABLE IF NOT EXISTS patients (id INTEGER PRIMARY KEY AUTOINCREMENT, patient_no TEXT NOT NULL UNIQUE, full_name TEXT NOT NULL, gender TEXT NOT NULL, birth_date TEXT, contact TEXT, address TEXT, emergency_contact TEXT, status TEXT NOT NULL DEFAULT 'Active', created_at TEXT NOT NULL)");
 $pdo->exec("CREATE TABLE IF NOT EXISTS doctors (id INTEGER PRIMARY KEY AUTOINCREMENT, full_name TEXT NOT NULL, specialization TEXT NOT NULL, contact TEXT, room_no TEXT, status TEXT NOT NULL DEFAULT 'Active', created_at TEXT NOT NULL)");
 $pdo->exec("CREATE TABLE IF NOT EXISTS appointments (id INTEGER PRIMARY KEY AUTOINCREMENT, patient_id INTEGER NOT NULL, doctor_id INTEGER NOT NULL, appointment_date TEXT NOT NULL, appointment_time TEXT NOT NULL, reason TEXT, status TEXT NOT NULL DEFAULT 'Scheduled', created_at TEXT NOT NULL)");
 $pdo->exec("CREATE TABLE IF NOT EXISTS admissions (id INTEGER PRIMARY KEY AUTOINCREMENT, patient_id INTEGER NOT NULL, room_no TEXT NOT NULL, admission_date TEXT NOT NULL, discharge_date TEXT, diagnosis TEXT, status TEXT NOT NULL DEFAULT 'Admitted', created_at TEXT NOT NULL)");
 $pdo->exec("CREATE TABLE IF NOT EXISTS services (id INTEGER PRIMARY KEY AUTOINCREMENT, service_code TEXT NOT NULL UNIQUE, service_name TEXT NOT NULL, category TEXT NOT NULL, price REAL NOT NULL DEFAULT 0, status TEXT NOT NULL DEFAULT 'Active', created_at TEXT NOT NULL)");
 $pdo->exec("CREATE TABLE IF NOT EXISTS invoices (id INTEGER PRIMARY KEY AUTOINCREMENT, invoice_no TEXT NOT NULL UNIQUE, patient_id INTEGER NOT NULL, invoice_date TEXT NOT NULL, subtotal REAL NOT NULL DEFAULT 0, discount REAL NOT NULL DEFAULT 0, tax_rate REAL NOT NULL DEFAULT 0, tax_amount REAL NOT NULL DEFAULT 0, total_amount REAL NOT NULL DEFAULT 0, amount_paid REAL NOT NULL DEFAULT 0, balance REAL NOT NULL DEFAULT 0, payment_status TEXT NOT NULL DEFAULT 'Unpaid', notes TEXT, created_by INTEGER, created_at TEXT NOT NULL)");
 $pdo->exec("CREATE TABLE IF NOT EXISTS invoice_items (id INTEGER PRIMARY KEY AUTOINCREMENT, invoice_id INTEGER NOT NULL, service_id INTEGER, description TEXT NOT NULL, quantity INTEGER NOT NULL DEFAULT 1, unit_price REAL NOT NULL DEFAULT 0, line_total REAL NOT NULL DEFAULT 0)");
 seed($pdo); return $pdo;
}
function seed($pdo){
 if((int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn()===0){$s=$pdo->prepare("INSERT INTO users(name,email,password,role,status,created_at) VALUES(?,?,?,?,?,?)");$s->execute(['Hospital Administrator','admin@hospital.test','admin123','admin','Active',date('Y-m-d H:i:s')]);$s->execute(['Billing Officer','billing@hospital.test','billing123','billing','Active',date('Y-m-d H:i:s')]);}
 if((int)$pdo->query("SELECT COUNT(*) FROM patients")->fetchColumn()===0){$s=$pdo->prepare("INSERT INTO patients(patient_no,full_name,gender,birth_date,contact,address,emergency_contact,status,created_at) VALUES(?,?,?,?,?,?,?,?,?)");$s->execute(['P-1001','Maria Santos','Female','1995-04-12','09171234567','Quezon City','Ana Santos - 09181234567','Active',date('Y-m-d H:i:s')]);$s->execute(['P-1002','Jose Reyes','Male','1988-11-22','09221234567','Manila City','Carlo Reyes - 09231234567','Active',date('Y-m-d H:i:s')]);$s->execute(['P-1003','Lina Cruz','Female','2001-07-08','09351234567','Pasig City','Nora Cruz - 09361234567','Active',date('Y-m-d H:i:s')]);}
 if((int)$pdo->query("SELECT COUNT(*) FROM doctors")->fetchColumn()===0){$s=$pdo->prepare("INSERT INTO doctors(full_name,specialization,contact,room_no,status,created_at) VALUES(?,?,?,?,?,?)");$s->execute(['Dr. Adrian Lim','Internal Medicine','09190000001','302','Active',date('Y-m-d H:i:s')]);$s->execute(['Dr. Camille Torres','Pediatrics','09190000002','210','Active',date('Y-m-d H:i:s')]);$s->execute(['Dr. Ramon Villanueva','Cardiology','09190000003','415','Active',date('Y-m-d H:i:s')]);}
 if((int)$pdo->query("SELECT COUNT(*) FROM services")->fetchColumn()===0){$s=$pdo->prepare("INSERT INTO services(service_code,service_name,category,price,status,created_at) VALUES(?,?,?,?,?,?)");$s->execute(['CONS-001','Doctor Consultation','Consultation',650,'Active',date('Y-m-d H:i:s')]);$s->execute(['LAB-101','Complete Blood Count','Laboratory',450,'Active',date('Y-m-d H:i:s')]);$s->execute(['IMG-201','Chest X-Ray','Imaging',900,'Active',date('Y-m-d H:i:s')]);$s->execute(['ROOM-301','Private Room Daily Rate','Room',2500,'Active',date('Y-m-d H:i:s')]);$s->execute(['MED-401','Medicine Package','Pharmacy',1200,'Active',date('Y-m-d H:i:s')]);}
}
function next_patient_no($pdo){$c=(int)$pdo->query("SELECT COUNT(*) FROM patients")->fetchColumn();return 'P-'.str_pad((string)($c+1001),4,'0',STR_PAD_LEFT);} 
$pdo=db(); $message='';
if(isset($_GET['logout'])){$_SESSION=[];session_unset();session_destroy();header('Location: index.php');exit;}
if(!isset($_SESSION['admin_id']) && $_SERVER['REQUEST_METHOD']==='POST' && ($_POST['form_type']??'')==='login'){$email=strtolower(trim($_POST['email']??''));$password=trim($_POST['password']??'');$s=$pdo->prepare("SELECT * FROM users WHERE email=? AND password=? AND role='admin' AND status='Active' LIMIT 1");$s->execute([$email,$password]);$admin=$s->fetch(PDO::FETCH_ASSOC);if($admin){$_SESSION['admin_id']=$admin['id'];$_SESSION['admin_name']=$admin['name'];header('Location: index.php');exit;}else{$message='Invalid admin login.';}}
if(!isset($_SESSION['admin_id'])):
?>
<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Panacea Admin Login</title><style>
*{box-sizing:border-box}
body{
    margin:0;
    min-height:100vh;
    display:grid;
    place-items:center;
    font-family:Inter,Segoe UI,Arial,sans-serif;
    color:#102033;
    background:
        radial-gradient(circle at 16% 18%,rgba(198,255,118,.48),transparent 22%),
        radial-gradient(circle at 86% 16%,rgba(92,190,255,.40),transparent 28%),
        linear-gradient(135deg,#eaf7ff,#f7fbff 48%,#e8f6ff);
}
.login-card{
    width:445px;
    background:rgba(255,255,255,.94);
    border-radius:28px;
    padding:34px;
    box-shadow:0 28px 70px rgba(48,118,174,.20);
    border:1px solid rgba(119,174,214,.28);
}
.brand{
    width:72px;
    height:72px;
    border-radius:20px;
    background:linear-gradient(135deg,#d9ff74,#66e4d2);
    color:#102033;
    display:grid;
    place-items:center;
    font-weight:1000;
    font-size:28px;
    margin-bottom:18px;
    box-shadow:0 12px 28px rgba(87,196,173,.20);
}
h1{
    margin:0;
    color:#111827;
    font-size:31px;
    letter-spacing:-.5px;
}
p{
    margin:8px 0 24px;
    color:#53677c;
    font-weight:750;
}
label{
    display:block;
    margin:14px 0 7px;
    color:#24364b;
    font-size:13px;
    font-weight:900;
}
input{
    width:100%;
    padding:13px 15px;
    border:1px solid #c9dff0;
    border-radius:14px;
    outline:none;
    background:#f7fbff;
    color:#111827;
    font-size:14px;
}
input:focus{
    background:white;
    border-color:#3aa8ff;
    box-shadow:0 0 0 4px rgba(58,168,255,.16);
}
button{
    width:100%;
    margin-top:20px;
    padding:14px;
    border:0;
    border-radius:15px;
    background:linear-gradient(135deg,#0ea5e9,#11c3aa);
    color:white;
    font-weight:950;
    cursor:pointer;
    box-shadow:0 14px 28px rgba(14,165,233,.23);
}
.msg{
    background:#fee2e2;
    color:#991b1b;
    padding:12px 14px;
    border-radius:14px;
    margin-bottom:14px;
    font-weight:800;
}
/* admin login interface update*/
</style></head><body><div class="login-card"><div class="brand">H+</div><h1>Panacea Admin</h1><p>Hospital operations and patient records</p><?php if($message): ?><div class="msg"><?=htmlspecialchars($message)?></div><?php endif; ?><form method="post"><input type="hidden" name="form_type" value="login"><label>Email</label><input name="email" value="admin@hospital.test"><label>Password</label><input type="password" name="password" value="admin123"><button>Sign In</button></form></div></body></html>
<?php exit; endif;
if($_SERVER['REQUEST_METHOD']==='POST'){
 $type=$_POST['form_type']??'';
 if($type==='add_patient'){$pn=trim($_POST['patient_no']??'');if($pn==='')$pn=next_patient_no($pdo);$s=$pdo->prepare("INSERT INTO patients(patient_no,full_name,gender,birth_date,contact,address,emergency_contact,status,created_at) VALUES(?,?,?,?,?,?,?,?,?)");$s->execute([$pn,trim($_POST['full_name']??''),trim($_POST['gender']??'Male'),trim($_POST['birth_date']??''),trim($_POST['contact']??''),trim($_POST['address']??''),trim($_POST['emergency_contact']??''),trim($_POST['status']??'Active'),date('Y-m-d H:i:s')]);$message='Patient saved.';}
 if($type==='update_patient'){$s=$pdo->prepare("UPDATE patients SET patient_no=?,full_name=?,gender=?,birth_date=?,contact=?,address=?,emergency_contact=?,status=? WHERE id=?");$s->execute([trim($_POST['patient_no']??''),trim($_POST['full_name']??''),trim($_POST['gender']??'Male'),trim($_POST['birth_date']??''),trim($_POST['contact']??''),trim($_POST['address']??''),trim($_POST['emergency_contact']??''),trim($_POST['status']??'Active'),(int)$_POST['id']]);$message='Patient updated.';}
 if($type==='delete_patient'){$s=$pdo->prepare("DELETE FROM patients WHERE id=?");$s->execute([(int)$_POST['id']]);$message='Patient deleted.';}
 if($type==='add_doctor'){$s=$pdo->prepare("INSERT INTO doctors(full_name,specialization,contact,room_no,status,created_at) VALUES(?,?,?,?,?,?)");$s->execute([trim($_POST['doctor_name']??''),trim($_POST['specialization']??''),trim($_POST['doctor_contact']??''),trim($_POST['room_no']??''),trim($_POST['doctor_status']??'Active'),date('Y-m-d H:i:s')]);$message='Doctor saved.';}
 if($type==='update_doctor'){$s=$pdo->prepare("UPDATE doctors SET full_name=?,specialization=?,contact=?,room_no=?,status=? WHERE id=?");$s->execute([trim($_POST['doctor_name']??''),trim($_POST['specialization']??''),trim($_POST['doctor_contact']??''),trim($_POST['room_no']??''),trim($_POST['doctor_status']??'Active'),(int)$_POST['id']]);$message='Doctor updated.';}
 if($type==='delete_doctor'){$s=$pdo->prepare("DELETE FROM doctors WHERE id=?");$s->execute([(int)$_POST['id']]);$message='Doctor deleted.';}
 if($type==='add_appointment'){$s=$pdo->prepare("INSERT INTO appointments(patient_id,doctor_id,appointment_date,appointment_time,reason,status,created_at) VALUES(?,?,?,?,?,?,?)");$s->execute([(int)$_POST['patient_id'],(int)$_POST['doctor_id'],trim($_POST['appointment_date']??''),trim($_POST['appointment_time']??''),trim($_POST['reason']??''),trim($_POST['appointment_status']??'Scheduled'),date('Y-m-d H:i:s')]);$message='Appointment saved.';}
 if($type==='add_admission'){$s=$pdo->prepare("INSERT INTO admissions(patient_id,room_no,admission_date,discharge_date,diagnosis,status,created_at) VALUES(?,?,?,?,?,?,?)");$s->execute([(int)$_POST['admission_patient_id'],trim($_POST['admission_room_no']??''),trim($_POST['admission_date']??''),trim($_POST['discharge_date']??''),trim($_POST['diagnosis']??''),trim($_POST['admission_status']??'Admitted'),date('Y-m-d H:i:s')]);$message='Admission saved.';}
 if($type==='add_service'){$s=$pdo->prepare("INSERT INTO services(service_code,service_name,category,price,status,created_at) VALUES(?,?,?,?,?,?)");$s->execute([trim($_POST['service_code']??''),trim($_POST['service_name']??''),trim($_POST['category']??''),(float)($_POST['price']??0),trim($_POST['service_status']??'Active'),date('Y-m-d H:i:s')]);$message='Service saved.';}
 if($type==='update_service'){$s=$pdo->prepare("UPDATE services SET service_code=?,service_name=?,category=?,price=?,status=? WHERE id=?");$s->execute([trim($_POST['service_code']??''),trim($_POST['service_name']??''),trim($_POST['category']??''),(float)($_POST['price']??0),trim($_POST['service_status']??'Active'),(int)$_POST['id']]);$message='Service updated.';}
 if($type==='delete_service'){$s=$pdo->prepare("DELETE FROM services WHERE id=?");$s->execute([(int)$_POST['id']]);$message='Service deleted.';}
}
$patients=$pdo->query("SELECT * FROM patients ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);$doctors=$pdo->query("SELECT * FROM doctors ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);$appointments=$pdo->query("SELECT appointments.*, patients.full_name AS patient_name, doctors.full_name AS doctor_name FROM appointments JOIN patients ON patients.id=appointments.patient_id JOIN doctors ON doctors.id=appointments.doctor_id ORDER BY appointments.id DESC")->fetchAll(PDO::FETCH_ASSOC);$admissions=$pdo->query("SELECT admissions.*, patients.full_name AS patient_name FROM admissions JOIN patients ON patients.id=admissions.patient_id ORDER BY admissions.id DESC")->fetchAll(PDO::FETCH_ASSOC);$services=$pdo->query("SELECT * FROM services ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);$invoices=$pdo->query("SELECT invoices.*, patients.patient_no, patients.full_name AS patient_name FROM invoices JOIN patients ON patients.id=invoices.patient_id ORDER BY invoices.id DESC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Hospital Management System</title><style>
*{box-sizing:border-box}
html{scroll-behavior:smooth}
body{
    margin:0;
    font-family:Inter,Segoe UI,Arial,sans-serif;
    background:
        radial-gradient(circle at 0% 0%,rgba(125,211,252,.35),transparent 30%),
        linear-gradient(135deg,#eaf7ff,#f9fcff 44%,#edf8ff);
    color:#102033;
}
.layout{
    display:grid;
    grid-template-columns:292px 1fr;
    min-height:100vh;
}
.sidebar{
    background:rgba(236,247,255,.92);
    color:#102033;
    padding:24px 18px;
    position:sticky;
    top:0;
    height:100vh;
    overflow:auto;
    border-right:1px solid #d8e8f5;
    box-shadow:18px 0 45px rgba(76,136,185,.11);
}
.logo{
    width:58px;
    height:58px;
    border-radius:15px;
    background:#102033;
    color:#d9ff74;
    display:grid;
    place-items:center;
    font-weight:1000;
    font-size:23px;
    margin-bottom:18px;
    box-shadow:0 14px 26px rgba(16,32,51,.16);
}
.sidebar h1{
    font-size:26px;
    line-height:1.08;
    margin:0 0 12px;
    letter-spacing:-.5px;
}
.sidebar p{
    color:#53677c;
    line-height:1.55;
    font-size:14px;
    font-weight:700;
}
.nav{
    display:grid;
    gap:9px;
    margin:22px 0;
}
.nav a{
    color:#22354a;
    text-decoration:none;
    background:rgba(255,255,255,.58);
    border:1px solid #dcebf7;
    padding:12px 14px;
    border-radius:15px;
    font-weight:850;
    font-size:13px;
    transition:.15s ease;
}
.nav a:hover{
    background:linear-gradient(135deg,#e7ff8f,#bff8ff);
    transform:translateX(2px);
}
.side-card{
    background:rgba(255,255,255,.72);
    border:1px solid #dcebf7;
    border-radius:18px;
    padding:15px;
    margin-top:16px;
    color:#102033;
}
.side-card b{
    display:block;
    color:#0c7aa8;
    margin-bottom:6px;
}
.logout{
    display:block;
    background:linear-gradient(135deg,#ef4444,#be123c);
    color:white;
    text-decoration:none;
    text-align:center;
    border-radius:15px;
    padding:13px;
    margin-top:18px;
    font-weight:900;
}
.main{
    padding:26px;
    max-width:1600px;
    width:100%;
}
.hero{
    background:rgba(255,255,255,.92);
    border:1px solid #dcebf7;
    border-radius:26px;
    padding:24px 28px;
    box-shadow:0 18px 42px rgba(76,136,185,.12);
    margin-bottom:20px;
}
.hero h2{
    margin:0;
    color:#111827;
    font-size:32px;
    letter-spacing:-.7px;
}
.hero p{
    margin:8px 0 0;
    color:#53677c;
    font-weight:750;
}
.stats{
    display:grid;
    grid-template-columns:repeat(6,1fr);
    gap:14px;
    margin-bottom:20px;
}
.stat{
    background:linear-gradient(180deg,#ffffff,#eaf7ff);
    color:#102033;
    border:1px solid #b7ddf7;
    border-radius:20px;
    padding:17px;
    box-shadow:0 12px 30px rgba(76,136,185,.10);
}
.stat span{
    color:#53677c;
    font-weight:900;
    text-transform:uppercase;
    font-size:11px;
    letter-spacing:.6px;
}
.stat strong{
    display:block;
    font-size:30px;
    margin-top:7px;
    color:#111827;
}
.msg{
    background:#dcfce7;
    color:#166534;
    padding:13px 16px;
    border-radius:16px;
    margin-bottom:18px;
    font-weight:900;
    border-left:6px solid #22c55e;
}
.section{
    background:rgba(255,255,255,.94);
    border-radius:24px;
    padding:22px;
    box-shadow:0 16px 38px rgba(76,136,185,.12);
    border:1px solid #dcebf7;
    margin-bottom:24px;
}
.section h3{
    margin:0 0 18px;
    color:#111827;
    font-size:23px;
    letter-spacing:-.4px;
}
.grid-2{
    display:grid;
    grid-template-columns:390px 1fr;
    gap:22px;
    align-items:start;
}
.form-grid{
    display:grid;
    grid-template-columns:1fr 1fr;
    gap:12px;
}
.form-grid .wide{grid-column:1 / -1}
label{
    display:block;
    color:#24364b;
    font-weight:900;
    font-size:12px;
    margin-bottom:6px;
    text-transform:uppercase;
    letter-spacing:.4px;
}
input,select,textarea{
    width:100%;
    min-height:42px;
    padding:10px 12px;
    border:1px solid #c9dff0;
    border-radius:13px;
    outline:none;
    background:#f7fbff;
    font-size:14px;
    color:#102033;
}
textarea{
    resize:vertical;
    min-height:88px;
}
input:focus,select:focus,textarea:focus{
    background:white;
    border-color:#3aa8ff;
    box-shadow:0 0 0 4px rgba(58,168,255,.14);
}
.btn{
    border:0;
    border-radius:13px;
    padding:12px 15px;
    font-weight:900;
    cursor:pointer;
}
.btn-primary{
    background:linear-gradient(135deg,#0ea5e9,#11c3aa);
    color:white;
}
.btn-danger{
    background:#be123c;
    color:white;
}
.btn-muted{
    background:#334155;
    color:white;
}
.table-wrap{overflow:auto}
table{
    width:100%;
    border-collapse:separate;
    border-spacing:0 9px;
    min-width:900px;
}
th{
    text-align:left;
    color:#53677c;
    text-transform:uppercase;
    font-size:12px;
    padding:0 10px;
}
td{
    background:#f8fbff;
    padding:12px 10px;
    border-top:1px solid #dcebf7;
    border-bottom:1px solid #dcebf7;
    vertical-align:middle;
}
td:first-child{
    border-left:1px solid #dcebf7;
    border-radius:14px 0 0 14px;
    font-weight:900;
}
td:last-child{
    border-right:1px solid #dcebf7;
    border-radius:0 14px 14px 0;
}
.inline{
    display:grid;
    grid-template-columns:repeat(4,1fr);
    gap:8px;
    align-items:end;
}
.inline .full{grid-column:1 / -1}
.actions{
    display:flex;
    gap:8px;
    flex-wrap:wrap;
}
.badge{
    display:inline-block;
    background:linear-gradient(135deg,#ecfccb,#cffafe);
    color:#0f766e;
    border-radius:999px;
    padding:6px 10px;
    font-weight:900;
    font-size:12px;
}
@media(max-width:1250px){
    .layout{grid-template-columns:1fr}
    .sidebar{height:auto;position:relative}
    .stats{grid-template-columns:repeat(2,1fr)}
    .grid-2{grid-template-columns:1fr}
}
</style></head><body><div class="layout"><aside class="sidebar"><div class="logo">H+</div><h1>Panacea Management</h1><p>Patient records, schedules, admissions, services, and invoice monitoring.</p><div class="nav"><a href="#patients">Patients</a><a href="#doctors">Doctors</a><a href="#appointments">Appointments</a><a href="#admissions">Admissions</a><a href="#services">Services</a><a href="#invoices">Invoices</a></div><div class="side-card"><b>Signed in</b><?=htmlspecialchars($_SESSION['admin_name'])?></div><div class="side-card"><b>Billing API</b>http://localhost:8000/api.php</div><a class="logout" href="?logout=1">Logout Admin</a></aside><main class="main"><div class="hero"><h2>Panacea Operations Dashboard</h2><p>Monitor patients, appointments, services, admissions, and billing activity in one clean workspace.</p></div><div class="stats"><div class="stat"><span>Patients</span><strong><?=count($patients)?></strong></div><div class="stat"><span>Doctors</span><strong><?=count($doctors)?></strong></div><div class="stat"><span>Appointments</span><strong><?=count($appointments)?></strong></div><div class="stat"><span>Admissions</span><strong><?=count($admissions)?></strong></div><div class="stat"><span>Services</span><strong><?=count($services)?></strong></div><div class="stat"><span>Invoices</span><strong><?=count($invoices)?></strong></div></div><?php if($message): ?><div class="msg"><?=htmlspecialchars($message)?></div><?php endif; ?>
<section class="section" id="patients"><h3>Patient Management</h3><div class="grid-2"><form method="post"><input type="hidden" name="form_type" value="add_patient"><div class="form-grid"><div><label>Patient No.</label><input name="patient_no" placeholder="Auto if blank"></div><div><label>Status</label><select name="status"><option>Active</option><option>Inactive</option></select></div><div class="wide"><label>Full Name</label><input name="full_name" required></div><div><label>Gender</label><select name="gender"><option>Male</option><option>Female</option><option>Other</option></select></div><div><label>Birth Date</label><input type="date" name="birth_date"></div><div><label>Contact</label><input name="contact"></div><div><label>Emergency Contact</label><input name="emergency_contact"></div><div class="wide"><label>Address</label><textarea name="address"></textarea></div><div class="wide"><button class="btn btn-primary" style="width:100%">Save Patient</button></div></div></form><div class="table-wrap"><table><tr><th>ID</th><th>Patient</th><th>Details</th><th>Contact</th><th>Status</th><th>Action</th></tr><?php foreach($patients as $p): ?><tr><td>#<?=$p['id']?></td><td><?=htmlspecialchars($p['patient_no'])?><br><b><?=htmlspecialchars($p['full_name'])?></b></td><td><?=htmlspecialchars($p['gender'])?><br><?=htmlspecialchars($p['birth_date'])?></td><td><?=htmlspecialchars($p['contact'])?><br><?=htmlspecialchars($p['emergency_contact'])?></td><td><span class="badge"><?=htmlspecialchars($p['status'])?></span></td><td><form method="post" class="inline"><input type="hidden" name="form_type" value="update_patient"><input type="hidden" name="id" value="<?=$p['id']?>"><input name="patient_no" value="<?=htmlspecialchars($p['patient_no'])?>"><input name="full_name" value="<?=htmlspecialchars($p['full_name'])?>"><select name="gender"><option <?=$p['gender']=='Male'?'selected':''?>>Male</option><option <?=$p['gender']=='Female'?'selected':''?>>Female</option><option <?=$p['gender']=='Other'?'selected':''?>>Other</option></select><input type="date" name="birth_date" value="<?=htmlspecialchars($p['birth_date'])?>"><input name="contact" value="<?=htmlspecialchars($p['contact'])?>"><input name="emergency_contact" value="<?=htmlspecialchars($p['emergency_contact'])?>"><input name="address" value="<?=htmlspecialchars($p['address'])?>"><select name="status"><option <?=$p['status']=='Active'?'selected':''?>>Active</option><option <?=$p['status']=='Inactive'?'selected':''?>>Inactive</option></select><div class="actions full"><button class="btn btn-primary">Update</button></form><form method="post"><input type="hidden" name="form_type" value="delete_patient"><input type="hidden" name="id" value="<?=$p['id']?>"><button class="btn btn-danger">Delete</button></form></div></td></tr><?php endforeach; ?></table></div></div></section>
<section class="section" id="doctors"><h3>Doctor Management</h3><div class="grid-2"><form method="post"><input type="hidden" name="form_type" value="add_doctor"><div class="form-grid"><div class="wide"><label>Doctor Name</label><input name="doctor_name" required></div><div><label>Specialization</label><input name="specialization" required></div><div><label>Room No.</label><input name="room_no"></div><div><label>Contact</label><input name="doctor_contact"></div><div><label>Status</label><select name="doctor_status"><option>Active</option><option>Inactive</option></select></div><div class="wide"><button class="btn btn-primary" style="width:100%">Save Doctor</button></div></div></form><div class="table-wrap"><table><tr><th>ID</th><th>Doctor</th><th>Specialization</th><th>Contact</th><th>Status</th><th>Action</th></tr><?php foreach($doctors as $d): ?><tr><td>#<?=$d['id']?></td><td><?=htmlspecialchars($d['full_name'])?><br>Room <?=htmlspecialchars($d['room_no'])?></td><td><?=htmlspecialchars($d['specialization'])?></td><td><?=htmlspecialchars($d['contact'])?></td><td><span class="badge"><?=htmlspecialchars($d['status'])?></span></td><td><form method="post" class="inline"><input type="hidden" name="form_type" value="update_doctor"><input type="hidden" name="id" value="<?=$d['id']?>"><input name="doctor_name" value="<?=htmlspecialchars($d['full_name'])?>"><input name="specialization" value="<?=htmlspecialchars($d['specialization'])?>"><input name="doctor_contact" value="<?=htmlspecialchars($d['contact'])?>"><input name="room_no" value="<?=htmlspecialchars($d['room_no'])?>"><select name="doctor_status"><option <?=$d['status']=='Active'?'selected':''?>>Active</option><option <?=$d['status']=='Inactive'?'selected':''?>>Inactive</option></select><button class="btn btn-primary">Update</button></form><form method="post" style="margin-top:8px"><input type="hidden" name="form_type" value="delete_doctor"><input type="hidden" name="id" value="<?=$d['id']?>"><button class="btn btn-danger">Delete</button></form></td></tr><?php endforeach; ?></table></div></div></section>
<section class="section" id="appointments"><h3>Appointment Scheduling</h3><div class="grid-2"><form method="post"><input type="hidden" name="form_type" value="add_appointment"><div class="form-grid"><div><label>Patient</label><select name="patient_id"><?php foreach($patients as $p): ?><option value="<?=$p['id']?>"><?=htmlspecialchars($p['full_name'])?></option><?php endforeach; ?></select></div><div><label>Doctor</label><select name="doctor_id"><?php foreach($doctors as $d): ?><option value="<?=$d['id']?>"><?=htmlspecialchars($d['full_name'])?></option><?php endforeach; ?></select></div><div><label>Date</label><input type="date" name="appointment_date" required></div><div><label>Time</label><input name="appointment_time" placeholder="09:00 AM" required></div><div><label>Status</label><select name="appointment_status"><option>Scheduled</option><option>Completed</option><option>Cancelled</option></select></div><div class="wide"><label>Reason</label><textarea name="reason"></textarea></div><div class="wide"><button class="btn btn-primary" style="width:100%">Save Appointment</button></div></div></form><div class="table-wrap"><table><tr><th>ID</th><th>Patient</th><th>Doctor</th><th>Schedule</th><th>Reason</th><th>Status</th></tr><?php foreach($appointments as $a): ?><tr><td>#<?=$a['id']?></td><td><?=htmlspecialchars($a['patient_name'])?></td><td><?=htmlspecialchars($a['doctor_name'])?></td><td><?=htmlspecialchars($a['appointment_date'])?><br><?=htmlspecialchars($a['appointment_time'])?></td><td><?=htmlspecialchars($a['reason'])?></td><td><span class="badge"><?=htmlspecialchars($a['status'])?></span></td></tr><?php endforeach; ?><?php if(!$appointments): ?><tr><td colspan="6">No appointment records yet.</td></tr><?php endif; ?></table></div></div></section>
<section class="section" id="admissions"><h3>Admission Records</h3><div class="grid-2"><form method="post"><input type="hidden" name="form_type" value="add_admission"><div class="form-grid"><div><label>Patient</label><select name="admission_patient_id"><?php foreach($patients as $p): ?><option value="<?=$p['id']?>"><?=htmlspecialchars($p['full_name'])?></option><?php endforeach; ?></select></div><div><label>Room No.</label><input name="admission_room_no" required></div><div><label>Admission Date</label><input type="date" name="admission_date" required></div><div><label>Discharge Date</label><input type="date" name="discharge_date"></div><div><label>Status</label><select name="admission_status"><option>Admitted</option><option>Discharged</option></select></div><div class="wide"><label>Diagnosis</label><textarea name="diagnosis"></textarea></div><div class="wide"><button class="btn btn-primary" style="width:100%">Save Admission</button></div></div></form><div class="table-wrap"><table><tr><th>ID</th><th>Patient</th><th>Room</th><th>Dates</th><th>Diagnosis</th><th>Status</th></tr><?php foreach($admissions as $a): ?><tr><td>#<?=$a['id']?></td><td><?=htmlspecialchars($a['patient_name'])?></td><td><?=htmlspecialchars($a['room_no'])?></td><td><?=htmlspecialchars($a['admission_date'])?><br><?=htmlspecialchars($a['discharge_date'])?></td><td><?=htmlspecialchars($a['diagnosis'])?></td><td><span class="badge"><?=htmlspecialchars($a['status'])?></span></td></tr><?php endforeach; ?><?php if(!$admissions): ?><tr><td colspan="6">No admission records yet.</td></tr><?php endif; ?></table></div></div></section>
<section class="section" id="services"><h3>Hospital Services and Charges</h3><div class="grid-2"><form method="post"><input type="hidden" name="form_type" value="add_service"><div class="form-grid"><div><label>Service Code</label><input name="service_code" required></div><div><label>Status</label><select name="service_status"><option>Active</option><option>Inactive</option></select></div><div class="wide"><label>Service Name</label><input name="service_name" required></div><div><label>Category</label><input name="category" required></div><div><label>Price</label><input type="number" step="0.01" name="price" required></div><div class="wide"><button class="btn btn-primary" style="width:100%">Save Service</button></div></div></form><div class="table-wrap"><table><tr><th>ID</th><th>Code</th><th>Service</th><th>Category</th><th>Price</th><th>Status</th><th>Action</th></tr><?php foreach($services as $s): ?><tr><td>#<?=$s['id']?></td><td><?=htmlspecialchars($s['service_code'])?></td><td><?=htmlspecialchars($s['service_name'])?></td><td><?=htmlspecialchars($s['category'])?></td><td>₱<?=number_format((float)$s['price'],2)?></td><td><span class="badge"><?=htmlspecialchars($s['status'])?></span></td><td><form method="post" class="inline"><input type="hidden" name="form_type" value="update_service"><input type="hidden" name="id" value="<?=$s['id']?>"><input name="service_code" value="<?=htmlspecialchars($s['service_code'])?>"><input name="service_name" value="<?=htmlspecialchars($s['service_name'])?>"><input name="category" value="<?=htmlspecialchars($s['category'])?>"><input name="price" value="<?=htmlspecialchars($s['price'])?>"><select name="service_status"><option <?=$s['status']=='Active'?'selected':''?>>Active</option><option <?=$s['status']=='Inactive'?'selected':''?>>Inactive</option></select><button class="btn btn-primary">Update</button></form><form method="post" style="margin-top:8px"><input type="hidden" name="form_type" value="delete_service"><input type="hidden" name="id" value="<?=$s['id']?>"><button class="btn btn-danger">Delete</button></form></td></tr><?php endforeach; ?></table></div></div></section>
<section class="section" id="invoices"><h3>Billing and Invoice Monitor</h3><div class="table-wrap"><table><tr><th>ID</th><th>Invoice</th><th>Patient</th><th>Date</th><th>Total</th><th>Paid</th><th>Balance</th><th>Status</th></tr><?php foreach($invoices as $i): ?><tr><td>#<?=$i['id']?></td><td><?=htmlspecialchars($i['invoice_no'])?></td><td><?=htmlspecialchars($i['patient_no'])?><br><b><?=htmlspecialchars($i['patient_name'])?></b></td><td><?=htmlspecialchars($i['invoice_date'])?></td><td>₱<?=number_format((float)$i['total_amount'],2)?></td><td>₱<?=number_format((float)$i['amount_paid'],2)?></td><td>₱<?=number_format((float)$i['balance'],2)?></td><td><span class="badge"><?=htmlspecialchars($i['payment_status'])?></span></td></tr><?php endforeach; ?><?php if(!$invoices): ?><tr><td colspan="8">No invoices yet. Create invoices from the C# Billing System.</td></tr><?php endif; ?></table></div></section></main></div></body></html>
