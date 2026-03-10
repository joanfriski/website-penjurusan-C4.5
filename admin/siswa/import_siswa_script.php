<script>
    // Script untuk validasi form
    (function() {
        'use strict';
        
        // Fetch all the forms we want to apply custom Bootstrap validation styles to
        var forms = document.querySelectorAll('.needs-validation');
        
        // Loop over them and prevent submission
        Array.prototype.slice.call(forms)
            .forEach(function(form) {
                form.addEventListener('submit', function(event) {
                    if (!form.checkValidity()) {
                        event.preventDefault();
                        event.stopPropagation();
                    }
                    
                    form.classList.add('was-validated');
                }, false);
            });
    })();
    
    // Fungsi untuk menyesuaikan content saat sidebar toggle
    $(document).ready(function() {
        function adjustContent() {
            if ($("#sidebar").hasClass("collapsed") || $("#sidebar").css("margin-left") === "-250px") {
                $("#content").addClass("expanded");
            } else {
                $("#content").removeClass("expanded");
            }
        }

        // Jalankan saat halaman dimuat
        adjustContent();

        // Listen untuk event sidebar toggle dari header
        $("#sidebarToggle").on("click", function() {
            setTimeout(function() {
                adjustContent();
            }, 10);
        });

        // Check saat window resize
        $(window).resize(function() {
            adjustContent();
            
            // Pada mobile, content selalu expanded
            if ($(window).width() <= 768) {
                $("#content").addClass("expanded");
            }
        });
        
        // Fungsi pembantu untuk escape karakter khusus di ID selector
        function escapeID(id) {
            return String(id).replace(/[!"#$%&'()*+,.\/:;<=>?@[\\\]^`{|}~]/g, "\\$&");
        }
        
        // Handling perubahan kolom kelas untuk mapping
        $("#kelas_col").change(function() {
            var kelasCol = $(this).val();
            if (!kelasCol) return;
            
            // Data preview dari PHP, di-convert ke JavaScript array
            var previewData = <?php echo json_encode($preview_data); ?>;
            var classList = <?php echo json_encode($kelas_list); ?>;
            
            // Ambil nilai kelas unik dari data preview
            var uniqueClasses = [];
            
            // Skip baris header jika ada
            var startRow = <?php echo isset($_POST['has_header']) ? 1 : 0; ?>;
            
            for (var i = startRow; i < previewData.length; i++) {
                if (previewData[i][kelasCol] && previewData[i][kelasCol].trim() !== '') {
                    if (!uniqueClasses.includes(previewData[i][kelasCol].trim())) {
                        uniqueClasses.push(previewData[i][kelasCol].trim());
                    }
                }
            }
            
            // Generate mapping UI
            var mappingHtml = '';
            
            if (uniqueClasses.length === 0) {
                mappingHtml = `
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Tidak ditemukan nilai kelas dalam data preview.
                    </div>
                `;
            } else {
                for (var i = 0; i < uniqueClasses.length; i++) {
                    var classValue = uniqueClasses[i];
                    var safeID = escapeID(classValue);
                    mappingHtml += `
                        <div class="mb-3">
                            <label for="kelas_map_${safeID}" class="form-label required-field">Kelas "${classValue}" di Excel</label>
                            <select class="form-select" id="kelas_map_${safeID}" name="kelas_map_${classValue}" required>
                                <option value="" selected disabled>-- Pilih Kelas di Sistem --</option>
                    `;
                    
                    // Cari kelas dengan nama yang sama untuk dipilih otomatis
                    var matchedClass = false;
                    
                    for (var j = 0; j < classList.length; j++) {
                        var selected = '';
                        // Jika nama kelas sama persis, pilih otomatis
                        if (classList[j].nama_kelas === classValue) {
                            selected = 'selected';
                            matchedClass = true;
                        }
                        mappingHtml += `<option value="${classList[j].id}" ${selected}>${classList[j].nama_kelas}</option>`;
                    }
                    
                    mappingHtml += `
                            </select>
                            ${matchedClass ? '<small class="text-success"><i class="fas fa-check-circle"></i> Kelas ditemukan dengan nama yang sama</small>' : ''}
                        </div>
                    `;
                }
            }
            
            // Update UI
            $("#kelasMapping").html(mappingHtml);
        });
    });
</script>