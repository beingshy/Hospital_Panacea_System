using System;
using System.Collections;
using System.Collections.Generic;
using System.Drawing;
using System.IO;
using System.Net;
using System.Web.Script.Serialization;
using System.Windows.Forms;

namespace HospitalInvoiceBillingApp
{
    internal static class Program
    {
        [STAThread]
        private static void Main()
        {
            Application.EnableVisualStyles();
            Application.SetCompatibleTextRenderingDefault(false);
            Application.Run(new LoginForm());
        }
    }

    public class ComboItem
    {
        public string Text;
        public string Value;
        public Dictionary<string, object> Data;
        public ComboItem(string text, string value, Dictionary<string, object> data) { Text = text; Value = value; Data = data; }
        public override string ToString() { return Text; }
    }

    public class InvoiceItemLine
    {
        public int ServiceId;
        public string Description;
        public int Quantity;
        public decimal UnitPrice;
        public decimal LineTotal;
    }

    public class LoginForm : Form
    {
        private string apiUrl = "http://localhost:8000/api.php";
        private JavaScriptSerializer json = new JavaScriptSerializer();
        private TextBox txtEmail = new TextBox();
        private TextBox txtPassword = new TextBox();
        private Label lblStatus = new Label();
        private Color ink = Color.FromArgb(255, 255, 255);
        private Color teal = Color.FromArgb(14, 165, 233);

        public LoginForm()
        {
            Text = "Panacea Billing Login";
            StartPosition = FormStartPosition.CenterScreen;
            Size = new Size(430, 500);
            FormBorderStyle = FormBorderStyle.FixedSingle;
            MaximizeBox = false;
            BackColor = Color.FromArgb(235, 247, 255);
            Font = new Font("Segoe UI", 9);

            Panel top = new Panel(); top.Location = new Point(0, 0); top.Size = new Size(430, 150); top.BackColor = Color.White; Controls.Add(top);
            Label badge = new Label(); badge.Text = "P+"; badge.BackColor = Color.FromArgb(208, 255, 109); badge.ForeColor = Color.FromArgb(16, 32, 51); badge.TextAlign = ContentAlignment.MiddleCenter; badge.Font = new Font("Segoe UI", 20, FontStyle.Bold); badge.Location = new Point(32, 32); badge.Size = new Size(72, 54); top.Controls.Add(badge);
            Label title = new Label(); title.Text = "Panacea Billing"; title.ForeColor = Color.FromArgb(16, 32, 51); title.Font = new Font("Segoe UI", 22, FontStyle.Bold); title.Location = new Point(32, 94); title.Size = new Size(340, 40); top.Controls.Add(title);

            Panel card = new Panel(); card.Location = new Point(32, 180); card.Size = new Size(350, 210); card.BackColor = Color.White; card.BorderStyle = BorderStyle.FixedSingle; Controls.Add(card);
            AddLabel(card, "Email", 24, 26); txtEmail.Location = new Point(24, 50); txtEmail.Size = new Size(302, 28); txtEmail.Text = "billing@hospital.test"; card.Controls.Add(txtEmail);
            AddLabel(card, "Password", 24, 92); txtPassword.Location = new Point(24, 116); txtPassword.Size = new Size(302, 28); txtPassword.PasswordChar = '*'; txtPassword.Text = "billing123"; card.Controls.Add(txtPassword);
            Button login = new Button(); login.Text = "LOGIN"; login.Location = new Point(24, 160); login.Size = new Size(302, 36); StyleButton(login, teal); login.Click += delegate { LoginBilling(); }; card.Controls.Add(login);
            lblStatus.ForeColor = teal; lblStatus.TextAlign = ContentAlignment.MiddleCenter; lblStatus.Location = new Point(32, 410); lblStatus.Size = new Size(350, 28); lblStatus.Font = new Font("Segoe UI", 8, FontStyle.Bold); Controls.Add(lblStatus);
        }

        private void AddLabel(Control parent, string text, int x, int y) { Label label = new Label(); label.Text = text; label.Location = new Point(x, y); label.Size = new Size(150, 20); label.Font = new Font("Segoe UI", 8, FontStyle.Bold); label.ForeColor = Color.FromArgb(51, 65, 85); parent.Controls.Add(label); }
        private void StyleButton(Button b, Color color)
        {
            b.UseVisualStyleBackColor = false;
            b.BackColor = color;
            b.ForeColor = Color.White;
            b.FlatStyle = FlatStyle.Flat;
            b.FlatAppearance.BorderSize = 0;
            b.Font = new Font("Segoe UI", 8, FontStyle.Bold);
            b.Cursor = Cursors.Hand;
        }

        private string PostApi(Dictionary<string, string> data)
        {
            using (WebClient client = new WebClient())
            {
                client.Headers[HttpRequestHeader.ContentType] = "application/x-www-form-urlencoded";
                List<string> parts = new List<string>();
                foreach (KeyValuePair<string, string> item in data) parts.Add(Uri.EscapeDataString(item.Key) + "=" + Uri.EscapeDataString(item.Value));
                try { return client.UploadString(apiUrl, "POST", string.Join("&", parts.ToArray())); }
                catch (WebException ex) { throw new Exception(ReadError(ex)); }
            }
        }

        private string ReadError(WebException ex)
        {
            try
            {
                if (ex.Response != null)
                {
                    using (Stream stream = ex.Response.GetResponseStream())
                    using (StreamReader reader = new StreamReader(stream))
                    {
                        string body = reader.ReadToEnd();
                        Dictionary<string, object> data = json.Deserialize<Dictionary<string, object>>(body);
                        if (data != null && data.ContainsKey("message")) return Convert.ToString(data["message"]);
                        return body;
                    }
                }
            }
            catch { }
            return ex.Message;
        }

        private void LoginBilling()
        {
            try
            {
                string response = PostApi(new Dictionary<string, string> { { "action", "login" }, { "email", txtEmail.Text.Trim() }, { "password", txtPassword.Text.Trim() }, { "role", "billing" } });
                Dictionary<string, object> result = json.Deserialize<Dictionary<string, object>>(response);
                Dictionary<string, object> user = result["user"] as Dictionary<string, object>;
                int id = Convert.ToInt32(user["id"]); string name = Convert.ToString(user["name"]); string email = Convert.ToString(user["email"]);
                MainForm main = new MainForm(id, name, email);
                main.FormClosed += delegate { if (Convert.ToString(main.Tag) == "logout") { txtPassword.Clear(); lblStatus.Text = "Logged out."; Show(); } else { Close(); } };
                Hide(); main.Show();
            }
            catch (Exception ex) { MessageBox.Show("Login failed.\n\n" + ex.Message + "\n\nMake sure PHP is running:\nphp -S localhost:8000"); }
        }
    }

    public class MainForm : Form
    {
        private string apiUrl = "http://localhost:8000/api.php";
        private JavaScriptSerializer json = new JavaScriptSerializer();
        private int userId;
        private string userName;
        private string userEmail;
        private Panel content = new Panel();
        private ComboBox cboPatient = new ComboBox();
        private ComboBox cboService = new ComboBox();
        private NumericUpDown numQty = new NumericUpDown();
        private NumericUpDown numCustomPrice = new NumericUpDown();
        private TextBox txtCustomDescription = new TextBox();
        private NumericUpDown numDiscount = new NumericUpDown();
        private NumericUpDown numTaxRate = new NumericUpDown();
        private NumericUpDown numAmountPaid = new NumericUpDown();
        private NumericUpDown numUpdatePaid = new NumericUpDown();
        private ComboBox cboPaymentStatus = new ComboBox();
        private TextBox txtNotes = new TextBox();
        private DataGridView gridItems = new DataGridView();
        private DataGridView gridInvoices = new DataGridView();
        private DataGridView gridInvoiceItems = new DataGridView();
        private Label lblTotals = new Label();
        private Label lblStatus = new Label();
        private List<InvoiceItemLine> invoiceItems = new List<InvoiceItemLine>();
        private Color ink = Color.FromArgb(16, 32, 51);
        private Color teal = Color.FromArgb(14, 165, 233);
        private Color mint = Color.FromArgb(208, 255, 109);

        public MainForm(int id, string name, string email)
        {
            userId = id; userName = name; userEmail = email;
            Text = "Panacea Billing and Invoice System";
            StartPosition = FormStartPosition.CenterScreen;
            Size = new Size(1240, 760);
            MinimumSize = new Size(1100, 680);
            BackColor = Color.FromArgb(235, 247, 255);
            Font = new Font("Segoe UI", 9);
            AutoScroll = true;
            BuildUi();
            Load += delegate { RefreshAll(); };
            Resize += delegate { CenterContent(); };
        }

        private void BuildUi()
        {
            content.Size = new Size(1180, 840); content.Location = new Point(18, 15); content.BackColor = Color.Transparent; Controls.Add(content);
            Panel side = new Panel(); side.Location = new Point(0, 0); side.Size = new Size(245, 720); side.BackColor = Color.FromArgb(231, 244, 255); content.Controls.Add(side);
            Label logo = new Label(); logo.Text = "P+"; logo.BackColor = Color.FromArgb(208, 255, 109); logo.ForeColor = Color.FromArgb(16, 32, 51); logo.TextAlign = ContentAlignment.MiddleCenter; logo.Font = new Font("Segoe UI", 20, FontStyle.Bold); logo.Location = new Point(25, 25); logo.Size = new Size(80, 60); side.Controls.Add(logo);
            Label title = new Label(); title.Text = "Panacea\nBilling"; title.ForeColor = Color.FromArgb(16, 32, 51); title.Font = new Font("Segoe UI", 16, FontStyle.Bold); title.Location = new Point(25, 108); title.Size = new Size(190, 70); side.Controls.Add(title);
            Label profile = new Label(); profile.Text = userName + "\n" + userEmail; profile.ForeColor = Color.FromArgb(45, 65, 85); profile.Font = new Font("Segoe UI", 8, FontStyle.Bold); profile.Location = new Point(25, 195); profile.Size = new Size(190, 65); side.Controls.Add(profile);
            Button refresh = new Button(); refresh.Text = "REFRESH DATA"; refresh.Location = new Point(25, 300); refresh.Size = new Size(190, 38); StyleButton(refresh, teal); refresh.Click += delegate { RefreshAll(); }; side.Controls.Add(refresh);
            Button clear = new Button(); clear.Text = "CLEAR INVOICE"; clear.Location = new Point(25, 350); clear.Size = new Size(190, 38); StyleButton(clear, Color.FromArgb(30, 41, 59)); clear.Click += delegate { ClearInvoice(); }; side.Controls.Add(clear);
            Button logout = new Button(); logout.Text = "LOGOUT"; logout.Location = new Point(25, 630); logout.Size = new Size(190, 38); StyleButton(logout, Color.FromArgb(190, 18, 60)); logout.Click += delegate { if (MessageBox.Show("Logout and return to login?", "Logout", MessageBoxButtons.YesNo) == DialogResult.Yes) { Tag = "logout"; Close(); } }; side.Controls.Add(logout);

            Panel top = Card(270, 0, 880, 95, "Invoice Workspace"); content.Controls.Add(top);
            Label subtitle = new Label(); subtitle.Text = "Create patient invoices, add billable services, record payments, and review billing history."; subtitle.ForeColor = Color.FromArgb(71, 85, 105); subtitle.Font = new Font("Segoe UI", 9, FontStyle.Bold); subtitle.Location = new Point(22, 55); subtitle.Size = new Size(800, 25); top.Controls.Add(subtitle);

            Panel invoice = Card(270, 115, 880, 330, "Create Invoice"); content.Controls.Add(invoice);
            AddLabel(invoice, "Patient", 22, 58); cboPatient.Location = new Point(100, 55); cboPatient.Size = new Size(300, 27); cboPatient.DropDownStyle = ComboBoxStyle.DropDownList; invoice.Controls.Add(cboPatient);
            AddLabel(invoice, "Service", 420, 58); cboService.Location = new Point(490, 55); cboService.Size = new Size(260, 27); cboService.DropDownStyle = ComboBoxStyle.DropDownList; invoice.Controls.Add(cboService);
            Button addService = new Button(); addService.Text = "ADD"; addService.Location = new Point(760, 54); addService.Size = new Size(80, 30); StyleButton(addService, teal); addService.Click += delegate { AddSelectedService(); }; invoice.Controls.Add(addService);
            AddLabel(invoice, "Custom Item", 22, 98); txtCustomDescription.Location = new Point(100, 95); txtCustomDescription.Size = new Size(300, 27); txtCustomDescription.Text = "Additional hospital charge"; invoice.Controls.Add(txtCustomDescription);
            AddLabel(invoice, "Qty", 420, 98); numQty.Location = new Point(490, 95); numQty.Size = new Size(70, 27); numQty.Minimum = 1; numQty.Maximum = 999; numQty.Value = 1; invoice.Controls.Add(numQty);
            AddLabel(invoice, "Price", 575, 98); numCustomPrice.Location = new Point(630, 95); numCustomPrice.Size = new Size(120, 27); numCustomPrice.DecimalPlaces = 2; numCustomPrice.Maximum = 1000000; invoice.Controls.Add(numCustomPrice);
            Button addCustom = new Button(); addCustom.Text = "ADD CUSTOM"; addCustom.Location = new Point(760, 94); addCustom.Size = new Size(80, 30); StyleButton(addCustom, teal); addCustom.Click += delegate { AddCustomItem(); }; invoice.Controls.Add(addCustom);
            gridItems.Location = new Point(22, 140); gridItems.Size = new Size(510, 135); ConfigureGrid(gridItems); invoice.Controls.Add(gridItems);
            AddLabel(invoice, "Discount", 555, 145); numDiscount.Location = new Point(650, 142); numDiscount.Size = new Size(120, 27); numDiscount.DecimalPlaces = 2; numDiscount.Maximum = 1000000; numDiscount.ValueChanged += delegate { UpdateTotals(); }; invoice.Controls.Add(numDiscount);
            AddLabel(invoice, "Tax %", 555, 180); numTaxRate.Location = new Point(650, 177); numTaxRate.Size = new Size(120, 27); numTaxRate.DecimalPlaces = 2; numTaxRate.Maximum = 100; numTaxRate.ValueChanged += delegate { UpdateTotals(); }; invoice.Controls.Add(numTaxRate);
            AddLabel(invoice, "Paid", 555, 215); numAmountPaid.Location = new Point(650, 212); numAmountPaid.Size = new Size(120, 27); numAmountPaid.DecimalPlaces = 2; numAmountPaid.Maximum = 1000000; numAmountPaid.ValueChanged += delegate { UpdateTotals(); }; invoice.Controls.Add(numAmountPaid);
            AddLabel(invoice, "Status", 555, 250); cboPaymentStatus.Location = new Point(650, 247); cboPaymentStatus.Size = new Size(120, 27); cboPaymentStatus.DropDownStyle = ComboBoxStyle.DropDownList; cboPaymentStatus.Items.Add("Unpaid"); cboPaymentStatus.Items.Add("Partial"); cboPaymentStatus.Items.Add("Paid"); cboPaymentStatus.SelectedIndex = 0; invoice.Controls.Add(cboPaymentStatus);
            lblTotals.Location = new Point(22, 280); lblTotals.Size = new Size(500, 25); lblTotals.ForeColor = teal; lblTotals.Font = new Font("Segoe UI", 8, FontStyle.Bold); invoice.Controls.Add(lblTotals);
            Button removeItem = new Button(); removeItem.Text = "REMOVE ITEM"; removeItem.Location = new Point(555, 282); removeItem.Size = new Size(130, 30); StyleButton(removeItem, Color.FromArgb(51, 65, 85)); removeItem.Click += delegate { RemoveSelectedItem(); }; invoice.Controls.Add(removeItem);
            Button saveInvoice = new Button(); saveInvoice.Text = "SAVE INVOICE"; saveInvoice.Location = new Point(700, 282); saveInvoice.Size = new Size(145, 30); StyleButton(saveInvoice, teal); saveInvoice.Click += delegate { SaveInvoice(); }; invoice.Controls.Add(saveInvoice);

            Panel history = Card(270, 465, 880, 250, "Invoice Records"); content.Controls.Add(history);
            gridInvoices.Location = new Point(22, 58); gridInvoices.Size = new Size(835, 150); ConfigureGrid(gridInvoices); gridInvoices.SelectionChanged += delegate { LoadSelectedInvoiceItems(); }; history.Controls.Add(gridInvoices);
            AddLabel(history, "Paid Amount", 22, 218); numUpdatePaid.Location = new Point(110, 215); numUpdatePaid.Size = new Size(120, 27); numUpdatePaid.DecimalPlaces = 2; numUpdatePaid.Maximum = 1000000; history.Controls.Add(numUpdatePaid);
            Button updatePay = new Button(); updatePay.Text = "UPDATE PAYMENT"; updatePay.Location = new Point(245, 214); updatePay.Size = new Size(150, 30); StyleButton(updatePay, teal); updatePay.Click += delegate { UpdateSelectedInvoicePayment(); }; history.Controls.Add(updatePay);

            Panel detail = Card(270, 735, 880, 85, "Selected Invoice Items"); content.Controls.Add(detail);
            gridInvoiceItems.Location = new Point(22, 48); gridInvoiceItems.Size = new Size(835, 30); ConfigureGrid(gridInvoiceItems); detail.Controls.Add(gridInvoiceItems);
            lblStatus.Location = new Point(270, 830); lblStatus.Size = new Size(880, 30); lblStatus.BackColor = Color.White; lblStatus.ForeColor = ink; lblStatus.Font = new Font("Segoe UI", 8, FontStyle.Bold); lblStatus.TextAlign = ContentAlignment.MiddleLeft; lblStatus.Padding = new Padding(10, 0, 0, 0); content.Controls.Add(lblStatus);
            RefreshInvoiceItemsGrid(); UpdateTotals(); CenterContent();
        }

        private Panel Card(int x, int y, int w, int h, string title)
        {
            Panel p = new Panel();
            p.Location = new Point(x, y);
            p.Size = new Size(w, h);
            p.BackColor = Color.White;
            p.BorderStyle = BorderStyle.FixedSingle;

            Label accent = new Label();
            accent.BackColor = Color.FromArgb(208, 255, 109);
            accent.Location = new Point(0, 0);
            accent.Size = new Size(7, h);
            p.Controls.Add(accent);

            Label t = new Label();
            t.Text = title;
            t.ForeColor = Color.FromArgb(16, 32, 51);
            t.Font = new Font("Segoe UI", 13, FontStyle.Bold);
            t.Location = new Point(22, 18);
            t.Size = new Size(w - 40, 30);
            p.Controls.Add(t);

            return p;
        }

        private void AddLabel(Control parent, string text, int x, int y)
        {
            Label label = new Label();
            label.Text = text;
            label.Location = new Point(x, y);
            label.Size = new Size(90, 23);
            label.Font = new Font("Segoe UI", 8, FontStyle.Bold);
            label.ForeColor = Color.FromArgb(36, 54, 75);
            parent.Controls.Add(label);
        }

        private void StyleButton(Button b, Color color)
        {
            b.UseVisualStyleBackColor = false;
            b.BackColor = color;
            b.ForeColor = Color.White;
            b.FlatStyle = FlatStyle.Flat;
            b.FlatAppearance.BorderSize = 0;
            b.Font = new Font("Segoe UI", 8, FontStyle.Bold);
            b.Cursor = Cursors.Hand;
            b.TextAlign = ContentAlignment.MiddleCenter;
        }

        private void ConfigureGrid(DataGridView g)
        {
            g.AllowUserToAddRows = false;
            g.AllowUserToDeleteRows = false;
            g.ReadOnly = true;
            g.RowHeadersVisible = false;
            g.SelectionMode = DataGridViewSelectionMode.FullRowSelect;
            g.MultiSelect = false;
            g.AutoSizeColumnsMode = DataGridViewAutoSizeColumnsMode.Fill;
            g.BackgroundColor = Color.White;
            g.BorderStyle = BorderStyle.FixedSingle;
            g.EnableHeadersVisualStyles = false;
            g.ColumnHeadersDefaultCellStyle.BackColor = Color.FromArgb(231, 244, 255);
            g.ColumnHeadersDefaultCellStyle.ForeColor = Color.FromArgb(16, 32, 51);
            g.ColumnHeadersDefaultCellStyle.Font = new Font("Segoe UI", 8, FontStyle.Bold);
            g.DefaultCellStyle.BackColor = Color.White;
            g.AlternatingRowsDefaultCellStyle.BackColor = Color.FromArgb(247, 251, 255);
            g.DefaultCellStyle.ForeColor = Color.FromArgb(16, 32, 51);
            g.DefaultCellStyle.SelectionBackColor = Color.FromArgb(14, 165, 233);
            g.DefaultCellStyle.SelectionForeColor = Color.White;
            g.DefaultCellStyle.Font = new Font("Segoe UI", 8);
            g.RowTemplate.Height = 26;
        }
        private void CenterContent() { int x = 18; if (ClientSize.Width > content.Width + 30) x = (ClientSize.Width - content.Width) / 2; content.Location = new Point(x, 15); }

        private Dictionary<string, object> GetApi(string query) { using (WebClient client = new WebClient()) { string response = client.DownloadString(apiUrl + "?" + query); return json.Deserialize<Dictionary<string, object>>(response); } }
        private string PostApi(Dictionary<string, string> data)
        {
            using (WebClient client = new WebClient())
            {
                client.Headers[HttpRequestHeader.ContentType] = "application/x-www-form-urlencoded";
                List<string> parts = new List<string>(); foreach (KeyValuePair<string, string> item in data) parts.Add(Uri.EscapeDataString(item.Key) + "=" + Uri.EscapeDataString(item.Value));
                try { return client.UploadString(apiUrl, "POST", string.Join("&", parts.ToArray())); } catch (WebException ex) { throw new Exception(ReadError(ex)); }
            }
        }
        private string ReadError(WebException ex) { try { if (ex.Response != null) { using (Stream stream = ex.Response.GetResponseStream()) using (StreamReader reader = new StreamReader(stream)) { string body = reader.ReadToEnd(); Dictionary<string, object> data = json.Deserialize<Dictionary<string, object>>(body); if (data != null && data.ContainsKey("message")) return Convert.ToString(data["message"]); return body; } } } catch { } return ex.Message; }

        private void RefreshAll() { try { LoadPatients(); LoadServices(); LoadInvoices(); lblStatus.Text = "Connected to hospital billing API."; } catch (Exception ex) { MessageBox.Show("Connection failed.\n\nRun PHP first inside php-api:\nphp -S localhost:8000\n\n" + ex.Message); } }
        private void LoadPatients()
        {
            Dictionary<string, object> result = GetApi("action=list_active_patients"); ArrayList rows = result["patients"] as ArrayList; cboPatient.Items.Clear();
            if (rows != null) foreach (object obj in rows) { Dictionary<string, object> p = obj as Dictionary<string, object>; if (p != null) cboPatient.Items.Add(new ComboItem(Convert.ToString(p["patient_no"]) + " - " + Convert.ToString(p["full_name"]), Convert.ToString(p["id"]), p)); }
            if (cboPatient.Items.Count > 0) cboPatient.SelectedIndex = 0;
        }
        private void LoadServices()
        {
            Dictionary<string, object> result = GetApi("action=list_active_services"); ArrayList rows = result["services"] as ArrayList; cboService.Items.Clear();
            if (rows != null) foreach (object obj in rows) { Dictionary<string, object> s = obj as Dictionary<string, object>; if (s != null) cboService.Items.Add(new ComboItem(Convert.ToString(s["service_code"]) + " - " + Convert.ToString(s["service_name"]) + " / PHP " + Convert.ToString(s["price"]), Convert.ToString(s["id"]), s)); }
            if (cboService.Items.Count > 0) cboService.SelectedIndex = 0;
        }
        private void LoadInvoices()
        {
            Dictionary<string, object> result = GetApi("action=list_invoices"); ArrayList rows = result["invoices"] as ArrayList; gridInvoices.Columns.Clear(); gridInvoices.Rows.Clear();
            gridInvoices.Columns.Add("id", "ID"); gridInvoices.Columns.Add("invoice", "Invoice No"); gridInvoices.Columns.Add("patient", "Patient"); gridInvoices.Columns.Add("date", "Date"); gridInvoices.Columns.Add("total", "Total"); gridInvoices.Columns.Add("paid", "Paid"); gridInvoices.Columns.Add("balance", "Balance"); gridInvoices.Columns.Add("status", "Status");
            if (rows != null) foreach (object obj in rows) { Dictionary<string, object> i = obj as Dictionary<string, object>; if (i != null) gridInvoices.Rows.Add(i["id"], i["invoice_no"], i["patient_name"], i["invoice_date"], i["total_amount"], i["amount_paid"], i["balance"], i["payment_status"]); }
        }
        private decimal ToDecimal(object value) { decimal result; decimal.TryParse(Convert.ToString(value), out result); return result; }
        private void AddSelectedService()
        {
            if (cboService.SelectedItem == null) { MessageBox.Show("Select a service first."); return; }
            ComboItem item = cboService.SelectedItem as ComboItem; Dictionary<string, object> data = item.Data; decimal price = ToDecimal(data["price"]);
            invoiceItems.Add(new InvoiceItemLine { ServiceId = Convert.ToInt32(data["id"]), Description = Convert.ToString(data["service_name"]), Quantity = (int)numQty.Value, UnitPrice = price, LineTotal = price * numQty.Value });
            RefreshInvoiceItemsGrid(); UpdateTotals();
        }
        private void AddCustomItem()
        {
            if (txtCustomDescription.Text.Trim() == "") { MessageBox.Show("Enter custom item description."); return; }
            invoiceItems.Add(new InvoiceItemLine { ServiceId = 0, Description = txtCustomDescription.Text.Trim(), Quantity = (int)numQty.Value, UnitPrice = numCustomPrice.Value, LineTotal = numCustomPrice.Value * numQty.Value });
            RefreshInvoiceItemsGrid(); UpdateTotals();
        }
        private void RemoveSelectedItem()
        {
            if (gridItems.SelectedRows.Count == 0) { MessageBox.Show("Select an invoice item first."); return; }
            int index = gridItems.SelectedRows[0].Index; if (index >= 0 && index < invoiceItems.Count) invoiceItems.RemoveAt(index);
            RefreshInvoiceItemsGrid(); UpdateTotals();
        }
        private void RefreshInvoiceItemsGrid()
        {
            gridItems.Columns.Clear(); gridItems.Rows.Clear(); gridItems.Columns.Add("desc", "Description"); gridItems.Columns.Add("qty", "Qty"); gridItems.Columns.Add("price", "Unit Price"); gridItems.Columns.Add("total", "Line Total");
            foreach (InvoiceItemLine item in invoiceItems) gridItems.Rows.Add(item.Description, item.Quantity, item.UnitPrice, item.LineTotal);
        }
        private void UpdateTotals()
        {
            decimal subtotal = 0; foreach (InvoiceItemLine item in invoiceItems) subtotal += item.LineTotal;
            decimal discount = numDiscount.Value; if (discount > subtotal) discount = subtotal; decimal taxable = subtotal - discount; decimal tax = taxable * (numTaxRate.Value / 100); decimal total = taxable + tax; decimal paid = numAmountPaid.Value; if (paid > total) paid = total; decimal balance = total - paid;
            lblTotals.Text = "Subtotal: PHP " + subtotal.ToString("N2") + "   Discount: PHP " + discount.ToString("N2") + "   Tax: PHP " + tax.ToString("N2") + "   Total: PHP " + total.ToString("N2") + "   Balance: PHP " + balance.ToString("N2");
        }
        private void SaveInvoice()
        {
            if (cboPatient.SelectedItem == null) { MessageBox.Show("Select a patient."); return; } if (invoiceItems.Count == 0) { MessageBox.Show("Add at least one invoice item."); return; }
            try
            {
                ComboItem patient = cboPatient.SelectedItem as ComboItem; ArrayList itemPayload = new ArrayList();
                foreach (InvoiceItemLine item in invoiceItems) { Dictionary<string, object> row = new Dictionary<string, object>(); row["service_id"] = item.ServiceId; row["description"] = item.Description; row["quantity"] = item.Quantity; row["unit_price"] = item.UnitPrice; itemPayload.Add(row); }
                string itemsJson = json.Serialize(itemPayload);
                string response = PostApi(new Dictionary<string, string> { { "action", "create_invoice" }, { "patient_id", patient.Value }, { "created_by", userId.ToString() }, { "discount", numDiscount.Value.ToString() }, { "tax_rate", numTaxRate.Value.ToString() }, { "amount_paid", numAmountPaid.Value.ToString() }, { "payment_status", cboPaymentStatus.Text }, { "notes", txtNotes.Text.Trim() }, { "items", itemsJson } });
                Dictionary<string, object> result = json.Deserialize<Dictionary<string, object>>(response); MessageBox.Show(Convert.ToString(result["message"]) + "\nInvoice: " + Convert.ToString(result["invoice_no"])); ClearInvoice(); LoadInvoices();
            }
            catch (Exception ex) { MessageBox.Show("Save invoice failed.\n\n" + ex.Message); }
        }
        private void ClearInvoice() { invoiceItems.Clear(); RefreshInvoiceItemsGrid(); numQty.Value = 1; numCustomPrice.Value = 0; numDiscount.Value = 0; numTaxRate.Value = 0; numAmountPaid.Value = 0; if (cboPaymentStatus.Items.Count > 0) cboPaymentStatus.SelectedIndex = 0; txtNotes.Clear(); UpdateTotals(); }
        private void LoadSelectedInvoiceItems()
        {
            if (gridInvoices.SelectedRows.Count == 0) return; string id = Convert.ToString(gridInvoices.SelectedRows[0].Cells[0].Value); decimal paid = ToDecimal(gridInvoices.SelectedRows[0].Cells[5].Value); if (paid <= numUpdatePaid.Maximum) numUpdatePaid.Value = paid;
            try
            {
                Dictionary<string, object> result = GetApi("action=get_invoice&id=" + Uri.EscapeDataString(id)); ArrayList rows = result["items"] as ArrayList; gridInvoiceItems.Columns.Clear(); gridInvoiceItems.Rows.Clear(); gridInvoiceItems.Columns.Add("desc", "Description"); gridInvoiceItems.Columns.Add("qty", "Qty"); gridInvoiceItems.Columns.Add("price", "Unit Price"); gridInvoiceItems.Columns.Add("total", "Line Total");
                if (rows != null) foreach (object obj in rows) { Dictionary<string, object> item = obj as Dictionary<string, object>; if (item != null) gridInvoiceItems.Rows.Add(item["description"], item["quantity"], item["unit_price"], item["line_total"]); }
            }
            catch { }
        }
        private void UpdateSelectedInvoicePayment()
        {
            if (gridInvoices.SelectedRows.Count == 0) { MessageBox.Show("Select an invoice first."); return; }
            try
            {
                string id = Convert.ToString(gridInvoices.SelectedRows[0].Cells[0].Value);
                string response = PostApi(new Dictionary<string, string> { { "action", "update_invoice_payment" }, { "id", id }, { "amount_paid", numUpdatePaid.Value.ToString() } });
                Dictionary<string, object> result = json.Deserialize<Dictionary<string, object>>(response); MessageBox.Show(Convert.ToString(result["message"])); LoadInvoices();
            }
            catch (Exception ex) { MessageBox.Show("Payment update failed.\n\n" + ex.Message); }
        }
    }
}
