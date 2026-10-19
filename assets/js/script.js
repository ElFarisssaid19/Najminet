document.addEventListener("DOMContentLoaded", function () {

  // ============ REVEAL ANIMATION ============
  document.querySelectorAll(".row").forEach(function (row) {
    row.querySelectorAll(":scope > [class*=col-] .reveal, :scope > .reveal")
      .forEach(function (el, index) {
        el.style.setProperty("--d", (index * 80) + "ms");
      });
  });

  if ("IntersectionObserver" in window) {
    const observer = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (entry.isIntersecting) {
          entry.target.classList.add("in");
          observer.unobserve(entry.target);
        }
      });
    }, { threshold: 0.15 });

    document.querySelectorAll(".reveal").forEach(function (el) {
      observer.observe(el);
    });
  } else {
    document.querySelectorAll(".reveal").forEach(function (el) {
      el.classList.add("in");
    });
  }

  setTimeout(function () {
    document.querySelectorAll(".reveal:not(.in)").forEach(function (el) {
      el.classList.add("in");
    });
  }, 2500);


  // ============ MOBILE MENU (Bootstrap offcanvas in includes/header.php) ============
  // Keeps the menu button's aria-expanded in sync and closes the menu when a
  // link is followed (Bootstrap only closes it on its own close button).
  document.querySelectorAll(".offcanvas[id]").forEach(function (panel) {
    const toggles = document.querySelectorAll('[data-bs-target="#' + panel.id + '"]');
    panel.addEventListener("shown.bs.offcanvas", function () {
      toggles.forEach(function (t) { t.setAttribute("aria-expanded", "true"); });
    });
    panel.addEventListener("hidden.bs.offcanvas", function () {
      toggles.forEach(function (t) { t.setAttribute("aria-expanded", "false"); });
    });
    panel.querySelectorAll("a[href]").forEach(function (link) {
      link.addEventListener("click", function () {
        if (window.bootstrap) bootstrap.Offcanvas.getOrCreateInstance(panel).hide();
      });
    });
  });


  // ============ VAT CALCULATOR ============
  (function () {
    const amountInput = document.getElementById("amount");
    if (!amountInput) return; 

    const rateSelect = document.getElementById("rate");
    const directionSelect = document.getElementById("direction");
    const resHT = document.getElementById("resHT");
    const resVAT = document.getElementById("resVAT");
    const resTTC = document.getElementById("resTTC");
    const calculateBtn = document.getElementById("calculateBtn");
    const resetBtn = document.getElementById("resetBtn");
    const errorMessage = document.getElementById("errorMessage");

    function formatMoney(value) {
      return value.toLocaleString("en-US", { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + " MAD";
    }
    function animate(el) {
      el.classList.add("result-updated");
      setTimeout(function () { el.classList.remove("result-updated"); }, 300);
    }
    function update(el, value) {
      el.textContent = formatMoney(value);
      animate(el);
    }

    function calculateVAT() {
      if (errorMessage) errorMessage.classList.add("d-none");

      const amount = parseFloat(amountInput.value);
      const rate = parseFloat(rateSelect.value);

      if (isNaN(amount) || amount < 0) {
        if (errorMessage) {
          errorMessage.textContent = "Please enter a valid amount.";
          errorMessage.classList.remove("d-none");
        }
        return;
      }

      let ht, vat, ttc;
      if (directionSelect.value === "ht-to-ttc") {
        ht = amount;
        vat = ht * rate / 100;
        ttc = ht + vat;
      } else {
        ttc = amount;
        ht = ttc / (1 + rate / 100);
        vat = ttc - ht;
      }

      update(resHT, ht);
      update(resVAT, vat);
      update(resTTC, ttc);
    }

    if (calculateBtn) calculateBtn.addEventListener("click", calculateVAT);

    if (resetBtn) {
      resetBtn.addEventListener("click", function () {
        amountInput.value = "";
        rateSelect.value = "20";
        directionSelect.value = "ht-to-ttc";
        resHT.textContent = "0.00 MAD";
        resVAT.textContent = "0.00 MAD";
        resTTC.textContent = "0.00 MAD";
        if (errorMessage) errorMessage.classList.add("d-none");
      });
    }

    amountInput.addEventListener("keypress", function (e) {
      if (e.key === "Enter") calculateVAT();
    });
  })();


  // ============ CNSS CALCULATOR ============
  (function () {
    const salaryInput = document.getElementById("salary");
    if (!salaryInput) return;

    const calculateCnssBtn = document.getElementById("calculateCnssBtn");
    const resetCnssBtn = document.getElementById("resetCnssBtn");
    const CEILING = 6000;

    function fmt(value) {
      return value.toLocaleString("en-US", { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + " MAD";
    }

    function calculateCNSS() {
      const salary = parseFloat(salaryInput.value);
      if (isNaN(salary) || salary < 0) return;

      const base = Math.min(salary, CEILING);
      const empCnss = base * 0.0448;
      const empAmo = salary * 0.0226;
      const netSalary = salary - empCnss - empAmo;

      const erCnss = base * 0.0898;
      const erAf = salary * 0.064;
      const erAmo = salary * 0.0411;
      const totalCost = salary + erCnss + erAf + erAmo;

      document.getElementById("empCnss").textContent = fmt(empCnss);
      document.getElementById("empAmo").textContent = fmt(empAmo);
      document.getElementById("netSalary").textContent = fmt(netSalary);
      document.getElementById("erCnss").textContent = fmt(erCnss);
      document.getElementById("erAf").textContent = fmt(erAf);
      document.getElementById("erAmo").textContent = fmt(erAmo);
      document.getElementById("totalCost").textContent = fmt(totalCost);
    }

    if (calculateCnssBtn) calculateCnssBtn.addEventListener("click", calculateCNSS);

    if (resetCnssBtn) {
      resetCnssBtn.addEventListener("click", function () {
        salaryInput.value = "";
        ["empCnss","empAmo","netSalary","erCnss","erAf","erAmo","totalCost"].forEach(function (id) {
          document.getElementById(id).textContent = "0.00 MAD";
        });
      });
    }

    salaryInput.addEventListener("keypress", function (e) {
      if (e.key === "Enter") calculateCNSS();
    });
  })();


  // ============ INCOME TAX CALCULATOR ============
  (function () {
    const periodEl = document.getElementById("period");
    const incomeEl = document.getElementById("income");
    if (!periodEl || !incomeEl) return;

    const dependentsEl = document.getElementById("dependents");
    const calculateIRBtn = document.getElementById("calculateIRBtn");
    const resetIRBtn = document.getElementById("resetIRBtn");

    const BRACKETS = [
      [0,       0.00, 0],
      [30001,   0.10, 3000],
      [50001,   0.20, 8000],
      [60001,   0.30, 14000],
      [80001,   0.34, 17200],
      [180001,  0.38, 24400],
    ];

    function fmt(n) {
      return n.toLocaleString("en-US", { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + " MAD";
    }

    function calculate() {
      let income = parseFloat(incomeEl.value) || 0;
      if (periodEl.value === "monthly") income = income * 12;

      let bracket = BRACKETS[0];
      for (const b of BRACKETS) {
        if (income >= b[0]) bracket = b;
      }

      let ir = Math.max(0, income * bracket[1] - bracket[2]);
      const dependents = dependentsEl ? (parseInt(dependentsEl.value) || 0) : 0;
      const familyDeduction = dependents * 360;
      ir = Math.max(0, ir - familyDeduction);

      document.getElementById("resIncome").textContent = fmt(income);
      document.getElementById("resRate").textContent = (bracket[1] * 100) + "%";
      document.getElementById("resDeduction").textContent = fmt(familyDeduction);
      document.getElementById("resIR").textContent = fmt(ir);
      document.getElementById("resMonthly").textContent = "Approx. " + fmt(ir / 12) + " / month";
    }

    if (calculateIRBtn) calculateIRBtn.addEventListener("click", calculate);

    if (resetIRBtn) {
      resetIRBtn.addEventListener("click", function () {
        periodEl.value = "annual";
        incomeEl.value = 120000;
        if (dependentsEl) dependentsEl.value = "0";
        document.getElementById("resIncome").textContent = "0.00 MAD";
        document.getElementById("resRate").textContent = "0%";
        document.getElementById("resDeduction").textContent = "0.00 MAD";
        document.getElementById("resIR").textContent = "0.00 MAD";
        document.getElementById("resMonthly").textContent = "";
      });
    }

    incomeEl.addEventListener("keypress", function (e) {
      if (e.key === "Enter") calculate();
    });

    
    calculate();
  })();


  // ============ INVOICE GENERATOR ============
  (function () {
    const itemsBody = document.getElementById("itemsBody");
    const vatRateEl = document.getElementById("vatRate");
    if (!itemsBody || !vatRateEl) return;

    function fmt(n) {
      return n.toLocaleString("en-US", { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + " MAD";
    }

    function addRow(desc, qty, price) {
      desc = desc || "";
      qty = qty === undefined ? 1 : qty;
      price = price === undefined ? 0 : price;

      const tr = document.createElement("tr");
      tr.innerHTML =
        '<td class="cell-wide" data-label="Description"><input type="text" class="desc" aria-label="Description" placeholder="Item description" value="' + desc + '"></td>' +
        '<td data-label="Qty"><input type="number" inputmode="numeric" class="qty" aria-label="Quantity" value="' + qty + '" min="0" step="1"></td>' +
        '<td data-label="Unit price (MAD)"><input type="number" inputmode="decimal" class="price" aria-label="Unit price" value="' + price + '" min="0" step="0.01"></td>' +
        '<td data-label="Total"><span class="mono lineTotal">0.00 MAD</span></td>' +
        '<td class="no-print text-center cell-remove"><button type="button" class="nn-btn nn-btn--danger nn-btn--icon remove-row" aria-label="Remove line"><span class="nn-icon nn-icon--trash" aria-hidden="true"></span></button></td>';

      itemsBody.appendChild(tr);
      tr.querySelectorAll("input").forEach(function (inp) {
        inp.addEventListener("input", calculate);
      });
      tr.querySelector(".remove-row").addEventListener("click", function () {
        tr.remove();
        calculate();
      });
      calculate();
    }

    function calculate() {
      let subtotal = 0;
      itemsBody.querySelectorAll("tr").forEach(function (tr) {
        const qty = parseFloat(tr.querySelector(".qty").value) || 0;
        const price = parseFloat(tr.querySelector(".price").value) || 0;
        const lineTotal = qty * price;
        tr.querySelector(".lineTotal").textContent = fmt(lineTotal);
        subtotal += lineTotal;
      });
      const vatRate = parseFloat(vatRateEl.value) / 100;
      const vat = subtotal * vatRate;
      const total = subtotal + vat;

      document.getElementById("subTotal").textContent = fmt(subtotal);
      document.getElementById("vatTotal").textContent = fmt(vat);
      document.getElementById("grandTotal").textContent = fmt(total);
    }

    const addRowBtn = document.getElementById("addRow");
    if (addRowBtn) addRowBtn.addEventListener("click", function () { addRow(); });

    vatRateEl.addEventListener("input", calculate);

    const printBtn = document.getElementById("printBtn");
    if (printBtn) printBtn.addEventListener("click", function () { window.print(); });

    const dateInput = document.getElementById("invDate");
    if (dateInput) dateInput.valueAsDate = new Date();

    // seed with 2 example rows
    addRow("Consulting services", 1, 1500);
    addRow("Software license", 2, 300);
  })();


  // ============ EXPENSE NOTE BUILDER ============
  (function () {
    const itemsBody = document.getElementById("itemsBody");
    const currencyEl = document.getElementById("currency");
    
    if (!itemsBody || !currencyEl) return;

    const CATEGORIES = ["Transport", "Meals", "Accommodation", "Office Supplies", "Software", "Other"];

    function fmt(n) {
      return n.toLocaleString("en-US", { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + " " + currencyEl.value;
    }

    function addRow(date, category, desc, amount) {
      date = date || "";
      category = category || "Transport";
      desc = desc || "";
      amount = amount === undefined ? "" : amount;

      const tr = document.createElement("tr");
      const options = CATEGORIES.map(function (c) {
        return '<option value="' + c + '" ' + (c === category ? "selected" : "") + ">" + c + "</option>";
      }).join("");

      tr.innerHTML =
        '<td class="cell-wide" data-label="Date"><input type="date" class="date" aria-label="Date" value="' + date + '"></td>' +
        '<td class="cell-wide" data-label="Category"><select class="category" aria-label="Category">' + options + "</select></td>" +
        '<td class="cell-wide" data-label="Description"><input type="text" class="desc" aria-label="Description" placeholder="Expense description" value="' + desc + '"></td>' +
        '<td data-label="Amount (MAD)"><input type="number" inputmode="decimal" class="amount" aria-label="Amount" placeholder="0.00" min="0" step="0.01" value="' + amount + '"></td>' +
        '<td class="no-print text-center cell-remove"><button type="button" class="nn-btn nn-btn--danger nn-btn--icon remove-row" aria-label="Remove expense"><span class="nn-icon nn-icon--trash" aria-hidden="true"></span></button></td>';

      itemsBody.appendChild(tr);
      tr.querySelectorAll("input, select").forEach(function (inp) {
        inp.addEventListener("input", calculate);
      });
      tr.querySelector(".remove-row").addEventListener("click", function () {
        tr.remove();
        calculate();
      });
      calculate();
    }

    function calculate() {
      let total = 0;
      itemsBody.querySelectorAll("tr").forEach(function (tr) {
        total += parseFloat(tr.querySelector(".amount").value) || 0;
      });
      document.getElementById("grandTotal").textContent = fmt(total);
    }

    const addRowBtn = document.getElementById("addRow");
    if (addRowBtn) addRowBtn.addEventListener("click", function () { addRow(); });

    currencyEl.addEventListener("input", calculate);

    const printBtn = document.getElementById("printBtn");
    if (printBtn) printBtn.addEventListener("click", function () { window.print(); });

    const today = new Date().toISOString().slice(0, 10);
    addRow(today, "Transport", "Taxi to client meeting", 80);
    addRow(today, "Meals", "Business lunch", 150);
  })();

});
