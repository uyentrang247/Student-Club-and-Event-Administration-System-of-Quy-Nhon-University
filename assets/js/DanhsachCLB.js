document.addEventListener("DOMContentLoaded", function () {

    const loadMoreBtn = document.getElementById("loadMoreBtn");
    const searchInput = document.getElementById("searchInput");
    const categoryFilter = document.getElementById("categoryFilter");
    const sortFilter = document.getElementById("sortFilter");
    const resetBtn = document.getElementById("resetBtn");
    const clubList = document.getElementById("club-list");

    // Lấy tất cả các CLB
    let allClubs = Array.from(document.querySelectorAll(".club-card"));
    let visibleCount = 6; // Số CLB hiển thị ban đầu

    // Hiển thị CLB ban đầu
    function initDisplay() {
        allClubs.forEach((club, index) => {
            if (index < visibleCount) {
                club.classList.remove("hidden-club");
                club.style.display = "flex";
            } else {
                club.classList.add("hidden-club");
                club.style.display = "none";
            }
        });
        updateLoadMoreButton();
    }

    // Nút "Xem thêm"
    if (loadMoreBtn) {
        loadMoreBtn.addEventListener("click", function () {
            let hiddenClubs = allClubs.filter(club => club.classList.contains("hidden-club"));
            
            let count = 0;
            hiddenClubs.forEach(function(club) {
                if (count < 6) {
                    club.classList.remove("hidden-club");
                    club.classList.add("show-club");
                    club.style.display = "flex";
                    count++;
                }
            });

            updateLoadMoreButton();
        });
    }

    // Cập nhật trạng thái nút "Xem thêm"
    function updateLoadMoreButton() {
        if (!loadMoreBtn) return;
        
        let hiddenClubs = allClubs.filter(club => 
            club.classList.contains("hidden-club")
        );
        
        if (hiddenClubs.length === 0) {
            loadMoreBtn.style.display = "none";
        } else {
            loadMoreBtn.style.display = "flex";
        }
    }

    // Tìm kiếm CLB
    if (searchInput) {
        searchInput.addEventListener("input", function() {
            filterClubs();
        });
    }

    // Lọc theo danh mục
    if (categoryFilter) {
        categoryFilter.addEventListener("change", function() {
            filterClubs();
        });
    }

    // Sắp xếp
    if (sortFilter) {
        sortFilter.addEventListener("change", function() {
            sortClubs();
        });
    }

    // Bỏ lọc
    if (resetBtn) {
        resetBtn.addEventListener("click", function() {
            searchInput.value = "";
            categoryFilter.value = "";
            sortFilter.value = "";
            
            // Reset về thứ tự ban đầu
            allClubs.forEach(club => {
                clubList.appendChild(club);
            });
            
            filterClubs();
        });
    }

    // Hàm lọc CLB
    function filterClubs() {
        const searchTerm = searchInput.value.toLowerCase().trim();
        const selectedCategory = categoryFilter.value.toLowerCase();
        
        let matchCount = 0;
        
        allClubs.forEach(club => {
            const clubName = club.querySelector("h2").textContent.toLowerCase();
            const clubCategory = club.querySelector(".badge").textContent.toLowerCase();
            
            const matchesSearch = clubName.includes(searchTerm);
            const matchesCategory = !selectedCategory || clubCategory.includes(selectedCategory);
            
            if (matchesSearch && matchesCategory) {
                club.style.display = "flex";
                club.classList.remove("hidden-club");
                matchCount++;
            } else {
                club.style.display = "none";
                club.classList.add("hidden-club");
            }
        });
        
        // Hiển thị thông báo nếu không tìm thấy
        showNoResultsMessage(matchCount);
        
        // Ẩn nút "Xem thêm" khi đang tìm kiếm
        if (searchTerm || selectedCategory) {
            if (loadMoreBtn) loadMoreBtn.style.display = "none";
        } else {
            // Reset về chế độ phân trang
            initDisplay();
        }
    }

    // Hàm sắp xếp CLB
    function sortClubs() {
        const sortValue = sortFilter.value;
        
        if (!sortValue) return;
        
        allClubs.sort((a, b) => {
            const nameA = a.querySelector("h2").textContent.trim();
            const nameB = b.querySelector("h2").textContent.trim();
            
            const membersA = parseInt(a.querySelector(".member-count").textContent.match(/\d+/)[0]);
            const membersB = parseInt(b.querySelector(".member-count").textContent.match(/\d+/)[0]);
            
            switch(sortValue) {
                case "name-asc":
                    return nameA.localeCompare(nameB, 'vi');
                case "name-desc":
                    return nameB.localeCompare(nameA, 'vi');
                case "members-desc":
                    return membersB - membersA;
                case "members-asc":
                    return membersA - membersB;
                default:
                    return 0;
            }
        });
        
        // Sắp xếp lại DOM
        allClubs.forEach(club => {
            clubList.appendChild(club);
        });
        
        filterClubs();
    }

    // Hiển thị thông báo không tìm thấy
    function showNoResultsMessage(count) {
        let noResultsMsg = document.getElementById("noResultsMsg");
        
        if (count === 0) {
            if (!noResultsMsg) {
                noResultsMsg = document.createElement("div");
                noResultsMsg.id = "noResultsMsg";
                noResultsMsg.className = "no-results";
                noResultsMsg.innerHTML = `
                    <div class="no-results-content">
                        <i class="ri-search-line"></i>
                        <h3>Không tìm thấy câu lạc bộ nào</h3>
                        <p>Thử tìm kiếm với từ khóa khác hoặc bỏ bộ lọc</p>
                    </div>
                `;
                clubList.appendChild(noResultsMsg);
            }
            noResultsMsg.style.display = "block";
            clubList.classList.add("has-no-results");
        } else {
            if (noResultsMsg) {
                noResultsMsg.style.display = "none";
            }
            clubList.classList.remove("has-no-results");
        }
    }

    // Khởi tạo
    initDisplay();

});
