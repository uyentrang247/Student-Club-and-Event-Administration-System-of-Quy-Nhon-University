function openPending() {
    fetch('popup_danhsachcho.php')
        .then(r => r.text())
        .then(html => {
            const box = document.getElementById("pendingContainer");
            box.innerHTML = html;

            const modal = document.getElementById("pendingModal");
            if (modal) {
                modal.classList.add("show");
                
                // Load script nếu chưa có
                if (!window.pendingActionsLoaded) {
                    const script = document.createElement('script');
                    script.src = 'assets/js/pending_actions.js';
                    script.onload = () => {
                        window.pendingActionsLoaded = true;
                        loadPendingList();
                    };
                    document.body.appendChild(script);
                } else {
                    loadPendingList();
                }
            }
        });
}

function loadPendingList() {
    // Lấy club_id từ URL
    const urlParams = new URLSearchParams(window.location.search);
    const clubId = urlParams.get('id') || '';
    
    fetch(`pending_list.php?club_id=${clubId}`)
        .then(r => {
            if (!r.ok) {
                throw new Error('Network response was not ok');
            }
            return r.text();
        })
        .then(html => {
            const list = document.getElementById("pending-list");
            if (list) list.innerHTML = html;
        })
        .catch(err => {
            console.error('Error loading pending list:', err);
            const list = document.getElementById("pending-list");
            if (list) {
                list.innerHTML = '<div class="empty-state"><h3>Lỗi</h3><p>Không thể tải danh sách. Vui lòng thử lại.</p></div>';
            }
        });
}

function closePending() {
    const modal = document.getElementById("pendingModal");
    if (!modal) return;

    modal.classList.remove("show");

    setTimeout(() => {
        document.getElementById("pendingContainer").innerHTML = "";
    }, 200);
}