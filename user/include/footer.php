<!-- Tutup div content-wrapper -->
</div>

<!-- JavaScript Libraries -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>

<!-- Custom Scripts -->
<script>
    $(document).ready(function() {
        // Aktifkan tooltips
        $('[data-bs-toggle="tooltip"]').tooltip();
        
        // Inisialisasi DataTables jika ada
        if($('.datatable').length > 0) {
            $('.datatable').DataTable({
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.11.5/i18n/id.json'
                },
                responsive: true
            });
        }
        
        // Toggle sidebar
        $("#sidebarToggle").click(function(e) {
            e.preventDefault();
            $("#sidebar").toggleClass("collapsed");
            $("#content").toggleClass("expanded");
        });
        
        // Tambahkan class CSS untuk collapsed sidebar
        $("<style>")
            .prop("type", "text/css")
            .html(`
                .sidebar.collapsed {
                    margin-left: -250px;
                }
                
                .sidebar .nav-link {
                    border-radius: 5px;
                    margin: 3px 10px;
                    padding: 8px 12px;
                    transition: all 0.2s;
                }
                
                .sidebar .nav-link:hover {
                    background-color: rgba(255, 255, 255, 0.2);
                }
                
                @media (max-width: 768px) {
                    .sidebar {
                        margin-left: -250px;
                    }
                    
                    .sidebar.show {
                        margin-left: 0;
                    }
                }
            `)
            .appendTo("head");
        
        // Responsive behavior
        function checkWidth() {
            if ($(window).width() <= 768) {
                $("#sidebar").addClass("collapsed");
                $("#content").addClass("expanded");
                
                // Toggle mobile sidebar
                $("#sidebarToggle").off('click').on('click', function(e) {
                    e.preventDefault();
                    $("#sidebar").toggleClass("show");
                });
            } else {
                // Toggle desktop sidebar
                $("#sidebarToggle").off('click').on('click', function(e) {
                    e.preventDefault();
                    $("#sidebar").toggleClass("collapsed");
                    $("#content").toggleClass("expanded");
                });
                
                // Jika tidak pernah di-toggle sebelumnya
                if (!$("#sidebar").hasClass("collapsed") && !$("#sidebar").hasClass("show")) {
                    $("#content").removeClass("expanded");
                }
            }
        }
        
        // Check width saat halaman dimuat
        checkWidth();
        
        // Check width saat ukuran window berubah
        $(window).resize(function() {
            checkWidth();
        });
        
        // Untuk menutup sidebar ketika mengklik di luar sidebar pada layar mobile
        $(document).click(function(e) {
            if ($(window).width() <= 768) {
                if (!$(e.target).closest('#sidebar').length && 
                    !$(e.target).closest('#sidebarToggle').length && 
                    $('#sidebar').hasClass('show')) {
                    $('#sidebar').removeClass('show');
                }
            }
        });
    });
</script>
</body>
</html>