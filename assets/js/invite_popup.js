document.addEventListener("DOMContentLoaded", () => {

    const openBtn = document.getElementById("openInviteBtn");

    if (openBtn) {
        openBtn.addEventListener("click", () => {

            fetch("invite_popup.php")
                .then(r => r.text())
                .then(html => {
                    document.getElementById("inviteContainer").innerHTML = html;

                    // Đợi popup load xong rồi gán event
                    initInvitePopup();
                });
        });
    }
});


// KHỞI TẠO POPUP
function initInvitePopup() {

    const popup = document.getElementById("invitePopup");
    const closeBtn = document.getElementById("closeInvitePopup");
    const cancelBtn = document.getElementById("inviteCancelBtn");
    const searchInput = document.getElementById("searchUser");
    const sendBtn = document.getElementById("inviteSendBtn");

    if (!popup) return;

    // Hiện popup
    popup.classList.add("show");


    // Đóng popup
    if (closeBtn) closeBtn.onclick = () => popup.classList.remove("show");
    if (cancelBtn) cancelBtn.onclick = () => popup.classList.remove("show");


    // TÌM KIẾM USER
    searchInput.oninput = function () {
        const keyword = this.value.trim();
        const resultBox = document.getElementById("searchResult");

        if (keyword.length < 2) {
            resultBox.innerHTML = "";
            resultBox.style.display = "none";
            return;
        }

        fetch("search_user.php?keyword=" + encodeURIComponent(keyword))
            .then(res => res.json())
            .then(data => {
                if (data.length === 0) {
                    resultBox.innerHTML = "<div class='search-item'>Không tìm thấy</div>";
                } else {
                    resultBox.innerHTML = data
                        .map(u => `<div class="search-item" data-id="${u.id}">${u.ho_ten} (${u.email})</div>`)
                        .join("");
                }

                resultBox.style.display = "block";

                document.querySelectorAll(".search-item").forEach(item => {
                    item.onclick = () => {
                        searchInput.value = item.innerText;
                        sendBtn.dataset.userid = item.dataset.id;
                        sendBtn.disabled = false;
                        resultBox.style.display = "none";
                    };
                });
            });
    };


    // GỬI LỜI MỜI
    sendBtn.onclick = function () {
        let user_id = this.dataset.userid;
        if (!user_id) return;

        // Lấy phòng ban được chọn
        const phongBanSelect = document.getElementById("invite_phong_ban_id");
        const phong_ban_id = phongBanSelect ? phongBanSelect.value : '';

        let formData = "user_id=" + user_id;
        if (phong_ban_id) {
            formData += "&phong_ban_id=" + phong_ban_id;
        }

        fetch("send_invite.php", {
            method: "POST",
            headers: {"Content-Type": "application/x-www-form-urlencoded"},
            body: formData
        })
        .then(res => res.text())
        .then(msg => {
            alert(msg);
            popup.classList.remove("show");
            // Reload trang để cập nhật danh sách
            window.location.reload();
        });
    };
}