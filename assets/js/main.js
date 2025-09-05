// assets/js/main.js

document.addEventListener("DOMContentLoaded", function () {
  const userRole = document.body.dataset.userRole || "user";
  const isAdmin = ["admin", "superadmin"].includes(userRole);

  // --- Utility Functions ---

  // Helper to format currency for display
  const formatRupiah = (number) => {
    return new Intl.NumberFormat("id-ID", {
      style: "currency",
      currency: "IDR",
      minimumFractionDigits: 0,
    }).format(number);
  };

  // Helper to format a plain number into Rupiah string for input field
  const formatNumberToRupiahString = (number) => {
    if (isNaN(number) || number === null || number === undefined) return "";
    return new Intl.NumberFormat("id-ID", {
      minimumFractionDigits: 0,
      maximumFractionDigits: 0,
    }).format(number);
  };

  // Helper to parse a Rupiah string from input field to a plain number
  const parseRupiahToNumber = (rupiahString) => {
    if (!rupiahString) return 0;
    // Remove "Rp", dots for thousands, and replace comma with dot for decimals (if any)
    const cleanString = rupiahString
      .replace(/[^0-9,-]+/g, "") // Keep only numbers, comma, hyphen
      .replace(/\./g, "") // Remove thousands separators (dots)
      .replace(/,/g, "."); // Replace decimal comma with dot (if decimal support is needed, currently 0 digits)

    const number = parseFloat(cleanString);
    return isNaN(number) ? 0 : number;
  };

  // --- Number Input Formatting Logic ---
  document.querySelectorAll('input[data-format="number"]').forEach((input) => {
    // Store the raw number value
    input.dataset.rawValue = parseRupiahToNumber(input.value);
    input.value = formatNumberToRupiahString(input.dataset.rawValue);

    input.addEventListener("input", function () {
      // Get caret position before formatting
      let caretPos = this.selectionStart;
      const initialValue = this.value;
      const initialLength = initialValue.length;

      // Clean the input (remove non-digits except comma/dot for potential decimals, then remove thousands separators)
      let cleaned = this.value.replace(/[^0-9]/g, ""); // Only allow digits for now, no decimal support (step="1000")
      if (cleaned === "") {
        this.value = "";
        this.dataset.rawValue = "";
        return;
      }

      // Convert to number, then format for display
      const number = parseInt(cleaned, 10);
      this.dataset.rawValue = number; // Store raw value
      this.value = formatNumberToRupiahString(number);

      // Adjust caret position
      const newLength = this.value.length;
      caretPos += newLength - initialLength;
      this.setSelectionRange(caretPos, caretPos);
    });

    input.addEventListener("focus", function () {
      // On focus, show the raw number without formatting
      const rawValue = this.dataset.rawValue;
      if (rawValue !== "" && rawValue !== undefined) {
        this.value = String(rawValue);
      } else {
        this.value = "";
      }
      // this.select(); // Select all text for easy editing
    });

    input.addEventListener("blur", function () {
      // On blur, format back to Rupiah string
      const rawValue = parseRupiahToNumber(this.value); // Re-parse in case user typed directly
      this.dataset.rawValue = rawValue;
      this.value = formatNumberToRupiahString(rawValue);
    });
  });

  // --- Intercept form submissions to send raw numbers ---
  document.querySelectorAll("form.amount-form").forEach((form) => {
    form.addEventListener("submit", function (event) {
      this.querySelectorAll('input[data-format="number"]').forEach((input) => {
        // Convert displayed formatted value back to raw number before submission
        input.value = parseRupiahToNumber(input.value);
      });
    });
  });

  // --- Mobile Sidebar Toggle ---
  const menuToggle = document.getElementById("menu-toggle");
  const sidebar = document.getElementById("sidebar");
  const mainContent = document.querySelector(".main-content");

  if (menuToggle && sidebar) {
    menuToggle.addEventListener("click", (e) => {
      e.stopPropagation(); // Prevent the mainContent click listener from firing immediately
      sidebar.classList.toggle("aktif");
    });
  }

  if (mainContent && sidebar) {
    // A click on the main content area will close an aktif mobile sidebar
    mainContent.addEventListener("click", () => {
      if (sidebar.classList.contains("aktif")) {
        sidebar.classList.remove("aktif");
      }
    });
  }

  // --- Global Modal Handling ---
  window.openModal = (modalId) => {
    const modal = document.getElementById(modalId);
    if (modal) {
      modal.style.display = "block";
    }
  };

  window.closeModal = (modalId) => {
    const modal = document.getElementById(modalId);
    if (modal) {
      modal.style.display = "none";
    }
  };

  // Close modal if user clicks outside of the modal-content
  window.addEventListener("click", (event) => {
    if (event.target.classList.contains("modal")) {
      event.target.style.display = "none";
    }
  });

  // --- Dynamic Form Population for Editing ---

  // For Transactions Page
  window.openEditTransactionModal = (data) => {
    document.getElementById("edit_id").value = data.id;
    document.getElementById("edit_transaction_date").value =
      data.transaction_date;
    document.getElementById("edit_name").value = data.name;
    document.getElementById("edit_description").value = data.description;
    document.getElementById("edit_type").value = data.type;

    // Format amount for display
    const editAmountInput = document.getElementById("edit_amount");
    editAmountInput.dataset.rawValue = data.amount; // Store raw value
    editAmountInput.value = formatNumberToRupiahString(data.amount); // Display formatted

    openModal("editTransactionModal");
  };

  // For Savings Page
  window.openEditSavingModal = (data) => {
    document.getElementById("edit_saving_id").value = data.id;
    document.getElementById("edit_saving_date").value = data.saving_date;
    document.getElementById("edit_member_name").value = data.member_name;
    document.getElementById("edit_saving_type").value = data.saving_type;

    // Format amount for display
    const editSavingAmountInput = document.getElementById("edit_saving_amount");
    editSavingAmountInput.dataset.rawValue = data.amount;
    editSavingAmountInput.value = formatNumberToRupiahString(data.amount);

    document.getElementById("edit_saving_description").value = data.description;
    openModal("editSavingModal");
  };

  // For Infaq Page
  window.openEditInfaqModal = (data) => {
    document.getElementById("edit_infaq_id").value = data.id;
    document.getElementById("edit_infaq_date").value = data.infaq_date;
    document.getElementById("edit_infaq_donor_name").value = data.donor_name;
    document.getElementById("edit_infaq_description").value = data.description;
    document.getElementById("edit_infaq_type").value = data.type;

    // Format amount for display
    const editInfaqAmountInput = document.getElementById("edit_infaq_amount");
    editInfaqAmountInput.dataset.rawValue = data.amount;
    editInfaqAmountInput.value = formatNumberToRupiahString(data.amount);

    openModal("editInfaqModal");
  };

  // For Loans Page (Main Record)
  window.openEditLoanModal = (data) => {
    document.getElementById("edit_loan_id").value = data.id;
    document.getElementById("edit_loan_date").value = data.loan_date;
    document.getElementById("edit_loan_member_name").value = data.member_name;

    // Format amount for display
    const editLoanAmountInput = document.getElementById("edit_loan_amount");
    editLoanAmountInput.dataset.rawValue = data.loan_amount;
    editLoanAmountInput.value = formatNumberToRupiahString(data.loan_amount);

    document.getElementById("edit_loan_tenor_months").value = data.tenor_months;
    document.getElementById("edit_loan_status").value = data.status;
    openModal("editLoanModal");
  };

  // ** NEW ** For Saving Details (AJAX)
  window.openSavingDetailModal = async (memberName) => {
    openModal("savingDetailModal");

    // Set loading states
    const title = document.getElementById("savingDetailTitle");
    const summaryDiv = document.getElementById("savingDetailSummary");
    const historyTableBody = document.querySelector(
      "#savingHistoryTable tbody"
    );

    const colspan = isAdmin ? 5 : 4;
    title.innerText = `Detail Simpanan: ${memberName}`;
    summaryDiv.innerHTML = "<p>Loading summary...</p>";
    historyTableBody.innerHTML = `<tr><td colspan="${colspan}">Loading history...</td></tr>`;

    try {
      const response = await fetch(
        `ajax_get_saving_details.php?member_name=${encodeURIComponent(
          memberName
        )}`
      );

      if (!response.ok) {
        throw new Error(
          `Network response was not ok. Status: ${response.status}`
        );
      }

      const data = await response.json();
      if (data.error) {
        throw new Error(data.error);
      }

      let totalAmount = 0;
      data.forEach((item) => (totalAmount += parseFloat(item.amount)));

      // Populate summary
      summaryDiv.innerHTML = `
                <div class="detail-grid">
                    <div><strong>Nama Anggota:</strong> ${memberName}</div>
                    <div><strong>Total Simpanan:</strong> ${formatRupiah(
                      totalAmount
                    )}</div>
                    <div><strong>Jumlah Transaksi:</strong> ${data.length}</div>
                </div>
            `;

      // Populate transaction history
      if (data.length > 0) {
        historyTableBody.innerHTML = data
          .map((s) => {
            // Escape the JSON data for use in the onclick attribute
            const rowData = JSON.stringify(s).replace(/"/g, "&quot;");
            const adminActions = isAdmin
              ? `<td class="actions-cell">
                               <button class="btn btn-sm btn-warning" onclick='openEditSavingModal(${rowData})'>Edit</button>
                               <form action="actions/handle_saving.php" method="POST" onsubmit="return confirm('Yakin ingin menghapus data ini?');" style="display:inline-block;">
                                   <input type="hidden" name="id" value="${
                                     s.id
                                   }">
                                   <input type="hidden" name="action" value="delete">
                                   <input type="hidden" name="csrf_token" value="${
                                     document.querySelector(
                                       'meta[name="csrf-token"]'
                                     ).content
                                   }">
                                   <button type="submit" class="btn btn-sm btn-danger">Hapus</button>
                               </form>
                           </td>`
              : "";

            return `
                        <tr>
                            <td>${new Date(s.saving_date).toLocaleDateString(
                              "id-ID",
                              {
                                day: "2-digit",
                                month: "short",
                                year: "numeric",
                              }
                            )}</td>
                            <td><span class="badge badge-info">${
                              s.saving_type.charAt(0).toUpperCase() +
                              s.saving_type.slice(1)
                            }</span></td>
                            <td class="text-right">${formatRupiah(
                              s.amount
                            )}</td>
                            <td>${s.description || "-"}</td>
                            ${adminActions}
                        </tr>
                    `;
          })
          .join("");
      } else {
        historyTableBody.innerHTML = `<tr><td colspan="${colspan}" style="text-align:center;">Tidak ada riwayat simpanan untuk anggota ini.</td></tr>`;
      }
    } catch (error) {
      console.error("Fetch error:", error);
      summaryDiv.innerHTML = `<p class="text-danger">Gagal memuat detail simpanan.</p>`;
      historyTableBody.innerHTML = `<tr><td colspan="${colspan}" class="text-danger">Gagal memuat riwayat.</td></tr>`;
    }
  };

  // For Loan Details & Payments (AJAX)
  window.openLoanDetailModal = async (loanId) => {
    openModal("loanDetailModal");

    // Set loading states
    const contentDiv = document.getElementById("loanDetailContent");
    const historyTableBody = document.querySelector(
      "#paymentHistoryTable tbody"
    );
    const title = document.getElementById("loanDetailTitle");
    title.innerText = "Detail Piutang";
    contentDiv.innerHTML = "<p>Loading details...</p>";
    historyTableBody.innerHTML =
      '<tr><td colspan="4">Loading history...</td></tr>';
    document.getElementById("payment_loan_id").value = loanId;

    try {
      const response = await fetch(
        `ajax_get_loan_details.php?loan_id=${loanId}`
      );
      if (!response.ok) {
        throw new Error(
          "Network response was not ok. Status: " + response.status
        );
      }

      const data = await response.json();
      if (data.error) {
        throw new Error(data.error);
      }

      const { loan, payments } = data;

      // Populate loan details
      title.innerText = `Detail Piutang: ${loan.member_name}`;
      contentDiv.innerHTML = `
                <div class="detail-grid">
                    <div><strong>Nama Peminjam:</strong> ${
                      loan.member_name
                    }</div>
                    <div><strong>Tanggal Pinjam:</strong> ${new Date(
                      loan.loan_date
                    ).toLocaleDateString("id-ID", {
                      day: "2-digit",
                      month: "short",
                      year: "numeric",
                    })}</div>
                    <div><strong>Jumlah Pinjaman:</strong> ${formatRupiah(
                      loan.loan_amount
                    )}</div>
                    <div><strong>Total Bayar:</strong> ${formatRupiah(
                      loan.total_paid
                    )}</div>
                    <div class="text-danger"><strong>Sisa Piutang:</strong> ${formatRupiah(
                      loan.remaining_amount
                    )}</div>
                    <div><strong>Tenor:</strong> ${
                      loan.tenor_months
                    } Bulan</div>
                </div>
            `;

      // Populate payment history
      if (payments.length > 0) {
        historyTableBody.innerHTML = payments
          .map(
            (p) => `
                    <tr>
                        <td>${new Date(p.payment_date).toLocaleDateString(
                          "id-ID",
                          { day: "2-digit", month: "short", year: "numeric" }
                        )}</td>
                        <td class="text-right">${formatRupiah(
                          p.payment_amount
                        )}</td>
                        <td>${p.payment_month_no || "-"}</td>
                        <td>${p.description || "-"}</td>
                    </tr>
                `
          )
          .join("");
      } else {
        historyTableBody.innerHTML =
          '<tr><td colspan="4" style="text-align:center;">Belum ada riwayat pembayaran.</td></tr>';
      }
    } catch (error) {
      console.error("Fetch error:", error);
      contentDiv.innerHTML = `<p class="text-danger">Gagal memuat detail piutang. ${error.message}</p>`;
      historyTableBody.innerHTML =
        '<tr><td colspan="4" class="text-danger">Gagal memuat riwayat.</td></tr>';
    }
  };

  // Add mobile table scroll indicators
  function addScrollIndicators() {
    const tableContainers = document.querySelectorAll(".table-responsive");

    tableContainers.forEach((container) => {
      const table = container.querySelector("table");
      if (!table) return;

      // Create scroll indicator
      const indicator = document.createElement("div");
      indicator.className = "scroll-indicator";
      indicator.innerHTML = "← Geser untuk melihat lebih →";

      // Only add indicator if table is wider than container AND no indicator exists
      if (
        table.scrollWidth > container.clientWidth &&
        !container.previousElementSibling?.classList.contains(
          "scroll-indicator"
        )
      ) {
        container.parentNode.insertBefore(indicator, container);

        // Hide indicator when user scrolls
        container.addEventListener("scroll", function () {
          if (this.scrollLeft > 10) {
            indicator.style.opacity = "0.3";
          } else {
            indicator.style.opacity = "1";
          }
        });
      }
    });
  }

  // Add mobile-friendly table headers (for very narrow screens)
  function enhanceMobileTables() {
    if (window.innerWidth <= 480) {
      const tables = document.querySelectorAll("table");

      tables.forEach((table) => {
        // Only apply if not already applied
        if (!table.classList.contains("table-stacked")) {
          table.classList.add("table-stacked");
          const headers = table.querySelectorAll("th");
          const rows = table.querySelectorAll("tbody tr");

          // Add data-label attributes for CSS styling
          rows.forEach((row) => {
            const cells = row.querySelectorAll("td");
            cells.forEach((cell, index) => {
              if (headers[index]) {
                cell.setAttribute("data-label", headers[index].textContent);
              }
            });
          });
        }
      });
    } else {
      // Remove table-stacked class if screen is wider
      document
        .querySelectorAll("table.table-stacked")
        .forEach((table) => table.classList.remove("table-stacked"));
    }
  }

  // Handle window resize
  function handleResize() {
    // Re-check scroll indicators on resize
    setTimeout(() => {
      // Remove existing indicators
      document
        .querySelectorAll(".scroll-indicator")
        .forEach((el) => el.remove());
      addScrollIndicators();
      enhanceMobileTables();
    }, 100);
  }

  // Initialize mobile enhancements
  if (window.innerWidth <= 768) {
    addScrollIndicators();
    enhanceMobileTables();
  }

  // Listen for window resize
  let resizeTimer;
  window.addEventListener("resize", function () {
    clearTimeout(resizeTimer);
    resizeTimer = setTimeout(handleResize, 250);
  });

  // Enhanced modal handling for mobile
  const originalOpenModal = window.openModal;
  window.openModal = function (modalId) {
    originalOpenModal(modalId);

    // Add mobile-specific modal behavior
    const modal = document.getElementById(modalId);
    if (modal && window.innerWidth <= 768) {
      // Prevent body scroll when modal is open
      document.body.style.overflow = "hidden";

      // Focus first input for better mobile UX
      const firstInput = modal.querySelector(
        'input[type="text"], input[type="date"], input[type="number"], select'
      );
      if (firstInput) {
        setTimeout(() => firstInput.focus(), 100);
      }
    }
  };

  const originalCloseModal = window.closeModal;
  window.closeModal = function (modalId) {
    originalCloseModal(modalId);

    // Re-enable body scroll
    document.body.style.overflow = "";

    // Re-apply formatting to any number inputs in the modal that might still be in raw state
    const modal = document.getElementById(modalId);
    if (modal) {
      modal.querySelectorAll('input[data-format="number"]').forEach((input) => {
        const rawValue = parseRupiahToNumber(input.value); // Ensure it's a number
        input.dataset.rawValue = rawValue;
        input.value = formatNumberToRupiahString(rawValue);
      });
    }
  };

  // Add touch-friendly interactions
  if ("ontouchstart" in window) {
    // Add touch class for CSS styling
    document.body.classList.add("touch-device");

    // Improve button tap targets
    const buttons = document.querySelectorAll(".btn-sm");
    buttons.forEach((btn) => {
      btn.style.minHeight = "44px"; // iOS recommended tap target
      btn.style.minWidth = "44px";
    });
  }

  // Optimize form inputs for mobile
  function optimizeMobileInputs() {
    // Prevent zoom on input focus (iOS) - this is primarily for type="text" inputs
    // For type="number", iOS might still zoom, but we've switched to type="text" for amounts
    const allInputs = document.querySelectorAll("input, select, textarea");
    allInputs.forEach((input) => {
      if (window.innerWidth <= 768) {
        const currentFontSize = window.getComputedStyle(input).fontSize;
        if (parseFloat(currentFontSize) < 16) {
          input.style.fontSize = "16px";
        }
      }
    });
  }

  optimizeMobileInputs();

  // Handle orientation change
  window.addEventListener("orientationchange", function () {
    setTimeout(() => {
      handleResize();
      optimizeMobileInputs();
    }, 500);
  });
});
