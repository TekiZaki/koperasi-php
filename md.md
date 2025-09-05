**Apply `escapeHtml()` to all dynamic content inserted via `innerHTML`**:

    - **In `window.openSavingDetailModal`**:

      ```javascript
      // ...
      // Populate summary
      summaryDiv.innerHTML = `
          <div class="detail-grid">
              <div><strong>Nama Anggota:</strong> ${escapeHtml(
                memberName
              )}</div>
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
            // No change here, as openEditSavingModal correctly uses .value
            const rowData = JSON.stringify(s).replace(/"/g, "&quot;");
            const adminActions = isAdmin
              ? `<td class="actions-cell">
                             <button class="btn btn-sm btn-warning" onclick='openEditSavingModal(${rowData})'>Edit</button>
                             <form action="actions/handle_saving.php" method="POST" onsubmit="return confirm('Yakin ingin menghapus data ini?');" style="display:inline-block;">
                                 <input type="hidden" name="id" value="${s.id}">
                                 <input type="hidden" name="action" value="delete">
                                 <button type="submit" class="btn btn-sm btn-danger">Hapus</button>
                             </form>
                         </td>`
              : "";

            return `
                      <tr>
                          <td>${escapeHtml(
                            new Date(s.saving_date).toLocaleDateString(
                              "id-ID",
                              {
                                day: "2-digit",
                                month: "short",
                                year: "numeric",
                              }
                            )
                          )}</td>
                          <td><span class="badge badge-info">${escapeHtml(
                            s.saving_type.charAt(0).toUpperCase() +
                              s.saving_type.slice(1)
                          )}</span></td>
                          <td class="text-right">${formatRupiah(s.amount)}</td>
                          <td>${escapeHtml(s.description || "-")}</td>
                          ${adminActions}
                      </tr>
                  `;
          })
          .join("");
      }
      // ...
      ```

    - **In `window.openLoanDetailModal`**:

      ```javascript
      // ...
      // Populate loan details
      title.innerText = `Detail Piutang: ${escapeHtml(loan.member_name)}`; // Also ensure title is escaped
      contentDiv.innerHTML = `
          <div class="detail-grid">
              <div><strong>Nama Peminjam:</strong> ${escapeHtml(
                loan.member_name
              )}</div>
              <div><strong>Tanggal Pinjam:</strong> ${escapeHtml(
                new Date(loan.loan_date).toLocaleDateString("id-ID", {
                  day: "2-digit",
                  month: "short",
                  year: "numeric",
                })
              )}</div>
              <div><strong>Jumlah Pinjaman:</strong> ${formatRupiah(
                loan.loan_amount
              )}</div>
              <div><strong>Total Bayar:</strong> ${formatRupiah(
                loan.total_paid
              )}</div>
              <div class="text-danger"><strong>Sisa Piutang:</strong> ${formatRupiah(
                loan.remaining_amount
              )}</div>
              <div><strong>Tenor:</strong> ${escapeHtml(
                loan.tenor_months
              )} Bulan</div>
          </div>
      `;

      // Populate payment history
      if (payments.length > 0) {
        historyTableBody.innerHTML = payments
          .map(
            (p) => `
                  <tr>
                      <td>${escapeHtml(
                        new Date(p.payment_date).toLocaleDateString("id-ID", {
                          day: "2-digit",
                          month: "short",
                          year: "numeric",
                        })
                      )}</td>
                      <td class="text-right">${formatRupiah(
                        p.payment_amount
                      )}</td>
                      <td>${escapeHtml(p.payment_month_no || "-")}</td>
                      <td>${escapeHtml(p.description || "-")}</td>
                  </tr>
              `
          )
          .join("");
      }
      // ...
      ```
