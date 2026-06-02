// ================================
//  BAU Resale Marketplace - Main JS (FULLY CORRECTED)
// ================================

// Global flag to prevent duplicate loads
let isLoadingProducts = false;
let lastLoadTime = 0;

function showToast(message, type = 'success') {
    const toast = document.createElement('div');
    toast.className = `toast ${type}`;
    toast.textContent = message;
    toast.style.cssText = `
        position: fixed; bottom: 20px; right: 20px;
        background: ${type === 'success' ? '#10b981' : '#ef4444'};
        color: white; padding: 12px 24px; border-radius: 60px;
        z-index: 11000; font-weight: 600; box-shadow: 0 4px 12px black;
        animation: fadeIn 0.3s ease;
    `;
    document.body.appendChild(toast);
    setTimeout(() => toast.remove(), 3000);
}

function escapeHtml(str) {
    if (!str) return '';
    return str.replace(/[&<>]/g, function(m) {
        if (m === '&') return '&amp;';
        if (m === '<') return '&lt;';
        if (m === '>') return '&gt;';
        return m;
    });
}

async function fetchAPI(url, options = {}) {
    const res = await fetch(url, { ...options, credentials: 'include' });
    const text = await res.text();
    try {
        const data = JSON.parse(text);
        if (!res.ok) throw new Error(data.error || 'Request failed');
        return data;
    } catch(e) {
        console.error('API error:', text);
        throw new Error('Server error: ' + text.substring(0, 100));
    }
}

function initTheme() {
    const saved = localStorage.getItem('theme') || 'dark';
    const link = document.querySelector('link[rel="stylesheet"][href*="style.css"]');
    if (link) link.href = saved === 'light' ? 'assets/css/style-light.css' : 'assets/css/style.css';
}

function ensureThemeToggle() {
    const container = document.querySelector('.nav-links');
    if (!container) return;
    if (document.getElementById('themeToggleBtn')) return;
    const btn = document.createElement('a');
    btn.id = 'themeToggleBtn';
    btn.href = '#';
    btn.className = 'theme-toggle';
    const cur = localStorage.getItem('theme') || 'dark';
    btn.innerHTML = cur === 'light' ? '🌙 Dark' : '☀️ Light';
    btn.style.cssText = 'margin-left: 1rem; padding: 0.4rem 1.2rem; border-radius: 60px; background: rgba(139,92,246,0.2);';
    btn.onclick = (e) => {
        e.preventDefault();
        const newTheme = cur === 'light' ? 'dark' : 'light';
        const link = document.querySelector('link[rel="stylesheet"][href*="style.css"]');
        if (link) link.href = newTheme === 'light' ? 'assets/css/style-light.css' : 'assets/css/style.css';
        localStorage.setItem('theme', newTheme);
        btn.innerHTML = newTheme === 'light' ? '🌙 Dark' : '☀️ Light';
        location.reload();
    };
    container.appendChild(btn);
}

async function updateNavbar() {
    const nav = document.querySelector('.nav-links');
    if (!nav) return;
    const isPublic = ['login.html', 'register.html'].some(p => location.pathname.includes(p));
    try {
        const data = await fetchAPI('api/get_user.php');
        if (data.loggedIn) {
            let navHtml = `<a href="index.html">Home</a>`;
            
            // Only show seller links for regular users (not admin)
            if (data.user.role !== 'admin') {
                navHtml += `
                    <a href="add-product.html">Sell</a>
                    <a href="my-products.html">My Items</a>
                    <a href="my-offers.html">Offers</a>
                    <a href="my-purchases.html">Purchases</a>
                `;
            }
            
            // Messages and Profile are shown to both
            navHtml += `
                <a href="messages.html">Messages</a>
                <a href="profile.html">${escapeHtml(data.user.name)}</a>
            `;
            
            // Admin Panel only for admin
            if (data.user.role === 'admin') {
                navHtml += `<a href="admin-dashboard.html">Admin Panel</a>`;
            }
            
            navHtml += `<a href="api/logout.php">Logout</a>`;
            nav.innerHTML = navHtml;
        } else if (!isPublic) {
            window.location.href = 'login.html';
            return;
        } else {
            nav.innerHTML = `<a href="login.html">Login</a><a href="register.html">Register</a>`;
        }
        const currentPage = window.location.pathname.split('/').pop() || 'index.html';
        document.querySelectorAll('.nav-links a').forEach(link => {
            if (link.getAttribute('href') === currentPage) link.classList.add('active');
        });
        ensureThemeToggle();
    } catch(err) {
        if (!isPublic) window.location.href = 'login.html';
    }
}

// ========== PRODUCT LISTING (WITH DUPLICATE PREVENTION) ==========
// ========== ENHANCED PRODUCT LISTING WITH SEARCH ==========
async function loadProducts(search = '', category = '') {
    const container = document.getElementById('productsGrid');
    if (!container) return;
    
    // Show loading state
    container.innerHTML = '<div class="loading-spinner">Loading products...</div>';
    
    // Build URL with parameters
    let url = 'api/get_products.php';
    const params = new URLSearchParams();
    
    if (search && search.trim() !== '') {
        params.append('search', search.trim());
    }
    if (category && category !== '' && category !== '0') {
        params.append('category', category);
    }
    
    if (params.toString()) {
        url += '?' + params.toString();
    }
    
    console.log('Fetching products from:', url); // Debug log
    
    try {
        const products = await fetchAPI(url);
        
        if (!products || products.length === 0) {
            let message = 'No products found.';
            if (search && category) {
                message = `No products found matching "${search}" in the selected category.`;
            } else if (search) {
                message = `No products found matching "${search}".`;
            } else if (category) {
                message = 'No products found in this category.';
            }
            container.innerHTML = `<div class="empty-state"><i class="fas fa-box-open"></i><p>${message}</p><a href="add-product.html" class="btn">Sell Something</a></div>`;
            return;
        }
        
        // Display products
        container.innerHTML = products.map(product => `
            <div class="product-card">
                <img src="assets/uploads/${product.primary_image || 'default.jpg'}" 
                     alt="${escapeHtml(product.title)}" 
                     onerror="this.src='assets/uploads/default.jpg'">
                <div class="product-info">
                    <div class="product-title">${escapeHtml(product.title)}</div>
                    <div class="product-price">৳ ${parseFloat(product.price).toFixed(2)}</div>
                    <div class="product-meta">
                        <i class="fas fa-user"></i> ${escapeHtml(product.seller_name)}
                    </div>
                    <a href="product.html?id=${product.id}" class="btn">View Details</a>
                </div>
            </div>
        `).join('');
        
    } catch (error) {
        console.error('Error loading products:', error);
        container.innerHTML = '<div class="error-state"><p>Error loading products. Please refresh the page.</p></div>';
    }
}

// ========== INITIALIZE SEARCH ==========
function initSearch() {
    const searchForm = document.getElementById('searchForm');
    const searchBtn = document.getElementById('searchBtn');
    const clearBtn = document.getElementById('clearBtn');
    const searchInput = document.getElementById('searchInput');
    const categorySelect = document.getElementById('categorySelect');
    
    // Handle search button click
    if (searchBtn) {
        searchBtn.addEventListener('click', () => {
            const searchTerm = searchInput ? searchInput.value : '';
            const category = categorySelect ? categorySelect.value : '';
            loadProducts(searchTerm, category);
        });
    }
    
    // Handle clear button
    if (clearBtn) {
        clearBtn.addEventListener('click', () => {
            if (searchInput) searchInput.value = '';
            if (categorySelect) categorySelect.value = '';
            loadProducts('', '');
        });
    }
    
    // Handle form submission
    if (searchForm) {
        searchForm.addEventListener('submit', (e) => {
            e.preventDefault();
            const searchTerm = searchInput ? searchInput.value : '';
            const category = categorySelect ? categorySelect.value : '';
            loadProducts(searchTerm, category);
        });
    }
    
    // Handle Enter key on search input
    if (searchInput) {
        searchInput.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                const category = categorySelect ? categorySelect.value : '';
                loadProducts(searchInput.value, category);
            }
        });
    }
    
    // Handle category change
    if (categorySelect) {
        categorySelect.addEventListener('change', () => {
            const searchTerm = searchInput ? searchInput.value : '';
            loadProducts(searchTerm, categorySelect.value);
        });
    }
}

// ========== PRODUCT DETAIL ==========
// ========== PRODUCT DETAIL ==========
async function loadProductDetail(id) {
    const container = document.getElementById('productDetail');
    if (!container) return;
    container.innerHTML = '<div>Loading...</div>';
    try {
        const p = await fetchAPI(`api/get_product.php?id=${id}`);
        document.title = p.title;
        container.innerHTML = `
            <div class="product-detail">
                <div class="product-gallery">
                    <img src="assets/uploads/${p.primary_image || 'default.jpg'}" id="mainImage" style="width:100%; max-width:400px;" onerror="this.src='assets/uploads/default.jpg'">
                    <div class="thumbnails">
                        ${p.images.map(img => `<img src="assets/uploads/${img.image_url}" class="thumbnail" data-img="${img.image_url}" style="width:60px; cursor:pointer;" onerror="this.src='assets/uploads/default.jpg'">`).join('')}
                    </div>
                </div>
                <div class="product-info-detail">
                    <h1>${escapeHtml(p.title)}</h1>
                    <div class="price">৳ ${p.price}</div>
                    <p><strong>Condition:</strong> ${p.condition}</p>
                    <p><strong>Location:</strong> ${p.location}</p>
                    <div class="seller-info">
                        <h3>Seller: <a href="view_profile.html?id=${p.seller_id}">${escapeHtml(p.seller_name)}</a></h3>
                        <p>Rating: ${p.seller_rating}/5</p>
                        ${p.seller_id != p.current_user_id ? `<button onclick="window.showReportModal(${p.seller_id}, ${p.id})" class="btn btn-danger" style="margin-top: 10px;"><i class="fas fa-flag"></i> Report Seller</button>` : ''}
                    </div>
                    <div class="description">
                        <h3>Description</h3>
                        <p>${escapeHtml(p.description).replace(/\n/g,'<br>')}</p>
                    </div>
                    ${p.seller_id != p.current_user_id ? `
                        <div class="actions">
                            <button onclick="window.showOfferModal(${p.id})" class="btn">Make Offer</button>
                            <button onclick="window.showContactModal(${p.id})" class="btn">Contact Seller</button>
                        </div>
                    ` : `
                        <div class="actions">
                            <a href="add-product.html?edit=${p.id}" class="btn">Edit</a>
                            <button onclick="window.deleteProduct(${p.id})" class="btn btn-danger">Delete</button>
                        </div>
                    `}
                </div>
            </div>
        `;
        document.querySelectorAll('.thumbnail').forEach(t => t.addEventListener('click', () => {
            document.getElementById('mainImage').src = 'assets/uploads/' + t.dataset.img;
        }));
    } catch(e) { 
        container.innerHTML = `<div class="alert alert-error">${e.message}</div>`;
    }
}

window.deleteProduct = async (id) => {
    if (confirm('Delete this product?')) {
        await fetchAPI(`api/delete_product.php?id=${id}`, { method: 'DELETE' });
        showToast('Deleted', 'success');
        window.location.href = 'my-products.html';
    }
};


window.showReportModal = function(reportedUserId, productId) {
    const overlay = document.createElement('div');
    overlay.className = 'modal-overlay';
    overlay.innerHTML = `
        <div class="modal">
            <span class="close">&times;</span>
            <h3><i class="fas fa-flag"></i> Report User</h3>
            <form id="reportForm">
                <div class="form-group">
                    <label>Reason for reporting</label>
                    <select name="reason" required>
                        <option value="">Select a reason...</option>
                        <option value="fraud">Fraud / Scam</option>
                        <option value="fake_product">Fake product</option>
                        <option value="rude_behavior">Rude / Abusive behavior</option>
                        <option value="wrong_product">Wrong product delivered</option>
                        <option value="no_response">No response from seller</option>
                        <option value="spam">Spam messages</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Detailed description</label>
                    <textarea name="message" rows="5" required placeholder="Please provide details about the issue..."></textarea>
                </div>
                <div class="form-group">
                    <small>Your report will be reviewed by an admin.</small>
                </div>
                <button type="submit" class="btn">Submit Report</button>
            </form>
        </div>
    `;
    document.body.appendChild(overlay);
    
    overlay.querySelector('.close').onclick = () => overlay.remove();
    overlay.onclick = (e) => { if (e.target === overlay) overlay.remove(); };
    
    overlay.querySelector('#reportForm').onsubmit = async (e) => {
        e.preventDefault();
        const reason = e.target.reason.value;
        const message = e.target.message.value;
        const fullMessage = `Reason: ${reason}\n\nDetails: ${message}`;
        
        const submitBtn = e.target.querySelector('button[type="submit"]');
        const originalText = submitBtn.textContent;
        submitBtn.disabled = true;
        submitBtn.textContent = 'Submitting...';
        
        try {
            const response = await fetch('api/file_complaint.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    reported_user_id: reportedUserId,
                    product_id: productId,
                    message: fullMessage
                }),
                credentials: 'include'
            });
            
            const text = await response.text();
            let data;
            try {
                data = JSON.parse(text);
            } catch(e) {
                throw new Error('Server returned: ' + text.substring(0, 200));
            }
            
            if (!response.ok) throw new Error(data.error || 'Failed to submit report');
            
            showToast(data.message || 'Report submitted successfully!', 'success');
            overlay.remove();
        } catch(err) {
            console.error('Report error:', err);
            showToast('Error: ' + err.message, 'error');
        } finally {
            submitBtn.disabled = false;
            submitBtn.textContent = originalText;
        }
    };
};

// ========== MY PRODUCTS ==========
async function loadMyProducts() {
    const container = document.getElementById('myProductsGrid');
    if (!container) return;
    container.innerHTML = '<div>Loading your products...</div>';
    try {
        const products = await fetchAPI('api/get_my_products.php');
        if (!products.length) { 
            container.innerHTML = '<p>You have no products. <a href="add-product.html">Sell now</a></p>'; 
            return; 
        }
        container.innerHTML = products.map(p => `
            <div class="product-card">
                <img src="assets/uploads/${p.primary_image || 'default.jpg'}" onerror="this.src='assets/uploads/default.jpg'">
                <div class="product-info">
                    <div class="product-title">${escapeHtml(p.title)}</div>
                    <div class="product-price">৳ ${p.price}</div>
                    <div>Status: ${p.status}</div>
                    <a href="product.html?id=${p.id}" class="btn">View</a>
                    <a href="add-product.html?edit=${p.id}" class="btn">Edit</a>
                    <button onclick="window.deleteProduct(${p.id})" class="btn btn-danger">Delete</button>
                </div>
            </div>
        `).join('');
    } catch(e) { 
        container.innerHTML = '<p>Error loading your products.</p>';
        console.error(e);
    }
}

// ========== OFFERS RECEIVED ==========
async function loadMyOffers() {
    const tbody = document.querySelector('#offersTable tbody');
    if (!tbody) return;
    
    tbody.innerHTML = '<tr><td colspan="7">Loading offers...</td></tr>';
    
    try {
        const offers = await fetchAPI('api/get_my_offers.php');
        
        if (!offers || offers.length === 0) {
            tbody.innerHTML = '<tr><td colspan="7">No offers received yet.</td></tr>';
            return;
        }
        
        tbody.innerHTML = offers.map(o => `
            <tr>
                <td>${escapeHtml(o.product_title)}</td>
                <td><a href="view_profile.html?id=${o.buyer_id}">${escapeHtml(o.buyer_name)}</a></td>
                <td>৳ ${parseFloat(o.offer_price).toFixed(2)}</td>
                <td>${escapeHtml(o.message || '-')}</td>
                <td class="status-${o.status}">${o.status.toUpperCase()}</td>
                <td>${new Date(o.created_at).toDateString()}</td>
                <td>
                    ${o.status === 'pending' ? `
                        <button onclick="window.acceptOffer(${o.id})" class="btn btn-success">Accept</button>
                        <button onclick="window.rejectOffer(${o.id})" class="btn btn-danger">Reject</button>
                    ` : '-'}
                </td>
            </tr>
        `).join('');
        
    } catch(e) {
        console.error('Error loading offers:', e);
        tbody.innerHTML = '<tr><td colspan="7">Error loading offers. Please refresh.</td></tr>';
    }
}

window.acceptOffer = async (id) => {
    if (confirm('Accept this offer? Product will be marked sold.')) {
        await fetchAPI(`api/accept_offer.php?id=${id}`, { method: 'POST' });
        showToast('Offer accepted', 'success');
        loadMyOffers();
    }
};
window.rejectOffer = async (id) => {
    await fetchAPI(`api/reject_offer.php?id=${id}`, { method: 'POST' });
    showToast('Offer rejected', 'info');
    loadMyOffers();
};

// ========== PURCHASES ==========
async function loadMyPurchases() {
    const container = document.getElementById('purchasesList');
    if (!container) return;
    container.innerHTML = '<div>Loading your purchases...</div>';
    try {
        const data = await fetchAPI('api/get_my_purchases.php');
        if (!data.length) { 
            container.innerHTML = '<p>You have no purchases.</p>'; 
            return; 
        }
        container.innerHTML = data.map(order => `
            <div class="product-card">
                <div class="product-info">
                    <div class="product-title">${escapeHtml(order.product_title)}</div>
                    <div>Price: ৳ ${order.offer_price}</div>
                    <div>Status: ${order.status}</div>
                    ${order.status === 'accepted' && !order.is_rated ? `<button onclick="window.rateSeller(${order.id}, ${order.seller_id}, ${order.product_id})" class="btn">Rate Seller</button>` : order.is_rated ? '<span>Rated ✓</span>' : ''}
                </div>
            </div>
        `).join('');
    } catch(e) { 
        container.innerHTML = '<p>Error loading purchases.</p>';
        console.error(e);
    }
}

window.rateSeller = (orderId, sellerId, productId) => {
    const overlay = document.createElement('div');
    overlay.className = 'modal-overlay';
    overlay.innerHTML = `<div class="modal"><span class="close">&times;</span><h3>Rate Seller</h3><form id="ratingForm"><div class="form-group"><label>Rating (1-5)</label><select name="rating" required><option value="1">1 - Poor</option><option value="2">2 - Fair</option><option value="3">3 - Good</option><option value="4">4 - Very Good</option><option value="5">5 - Excellent</option></select></div><div class="form-group"><label>Comment (optional)</label><textarea name="comment" rows="3"></textarea></div><button type="submit">Submit Rating</button></form></div>`;
    document.body.appendChild(overlay);
    overlay.querySelector('.close').onclick = () => overlay.remove();
    overlay.onclick = (e) => { if (e.target === overlay) overlay.remove(); };
    overlay.querySelector('#ratingForm').onsubmit = async (e) => {
        e.preventDefault();
        const fd = new FormData();
        fd.append('order_id', orderId);
        fd.append('seller_id', sellerId);
        fd.append('product_id', productId);
        fd.append('rating', e.target.rating.value);
        fd.append('comment', e.target.comment.value);
        try {
            await fetchAPI('api/rate_seller.php', { method: 'POST', body: fd });
            showToast('Thank you for rating!', 'success');
            overlay.remove();
            loadMyPurchases();
        } catch(err) { showToast(err.message, 'error'); }
    };
};

// ========== ADD/EDIT PRODUCT ==========
async function initProductForm() {
    const form = document.getElementById('productForm');
    if (!form) return;
    
    const urlParams = new URLSearchParams(window.location.search);
    const editId = urlParams.get('edit');
    
    // Load categories first
    try {
        const cats = await fetchAPI('api/get_categories.php');
        const sel = document.getElementById('category_id');
        if (sel && cats.length) {
            sel.innerHTML = '<option value="">-- Select Category --</option>';
            cats.forEach(c => { 
                let opt = document.createElement('option'); 
                opt.value = c.id; 
                opt.textContent = c.name; 
                sel.appendChild(opt); 
            });
        }
    } catch(e) {
        console.error('Failed to load categories:', e);
    }
    
    if (editId) {
        document.getElementById('formTitle').innerText = 'Edit Product';
        try {
            const p = await fetchAPI(`api/get_product.php?id=${editId}`);
            document.getElementById('title').value = p.title;
            document.getElementById('description').value = p.description;
            document.getElementById('price').value = p.price;
            setTimeout(() => {
                document.getElementById('category_id').value = p.category_id;
            }, 100);
            document.getElementById('condition').value = p.condition;
            document.getElementById('location').value = p.location;
            if (document.getElementById('status')) { 
                document.getElementById('status').value = p.status; 
                document.getElementById('statusGroup').style.display = 'block'; 
            }
        } catch(e) { 
            showToast('Could not load product data: ' + e.message, 'error'); 
        }
    }
    
    // Remove any existing listener to avoid duplicates
    form.removeEventListener('submit', form._submitHandler);
    form._submitHandler = async (e) => {
        e.preventDefault();
        const submitBtn = document.getElementById('submitBtn');
        const originalText = submitBtn.textContent;
        submitBtn.disabled = true;
        submitBtn.textContent = editId ? 'Updating...' : 'Adding...';
        
        const formData = new FormData(form);
        let url = 'api/add_product.php';
        if (editId) { 
            formData.append('id', editId); 
            url = 'api/edit_product.php'; 
        }
        
        try {
            const result = await fetchAPI(url, { method: 'POST', body: formData });
            showToast(editId ? 'Product updated!' : 'Product added!', 'success');
            window.location.href = 'my-products.html';
        } catch (err) { 
            showToast('Error: ' + err.message, 'error'); 
            submitBtn.disabled = false; 
            submitBtn.textContent = originalText;
        }
    };
    form.addEventListener('submit', form._submitHandler);
}

// ========== LOGIN ==========
function initLogin() {
    const form = document.getElementById('loginForm');
    if (!form) return;
    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        const fd = new FormData();
        fd.append('email', document.getElementById('email').value);
        fd.append('password', document.getElementById('password').value);
        try {
            await fetchAPI('api/login.php', { method: 'POST', body: fd });
            window.location.href = 'index.html';
        } catch(err) { 
            document.getElementById('loginMessage').innerHTML = `<div class="alert alert-error">${err.message}</div>`;
        }
    });
}

// ========== REGISTER ==========
function initRegister() {
    const form = document.getElementById('registerForm');
    if (!form) return;
    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        const fd = new FormData(form);
        try {
            const data = await fetchAPI('api/register.php', { method: 'POST', body: fd });
            if (data.success) {
                showToast(data.message || 'Account created successfully! Please login.', 'success');
                setTimeout(() => {
                    window.location.href = 'login.html';
                }, 2000);
            } else {
                showToast(data.error || 'Registration failed', 'error');
            }
        } catch (err) {
            showToast(err.message, 'error');
        }
    });
}

// ========== SEARCH (WITHOUT DUPLICATE EVENT) ==========
function initSearch() {
    const form = document.getElementById('searchForm');
    if (!form) return;
    
    // Remove existing listener to prevent duplicates
    form.removeEventListener('submit', form._searchHandler);
    
    form._searchHandler = (e) => {
        e.preventDefault();
        const search = document.getElementById('searchInput').value;
        const category = document.getElementById('categorySelect').value;
        loadProducts(search, category);
    };
    
    form.addEventListener('submit', form._searchHandler);
}

// ========== MODALS ==========
window.showOfferModal = function(productId) {
    const overlay = document.createElement('div');
    overlay.className = 'modal-overlay';
    overlay.innerHTML = `<div class="modal"><span class="close">&times;</span><h3>Make an Offer</h3><form id="offerForm"><div class="form-group"><label>Your Offer (৳)</label><input type="number" step="0.01" name="offer_price" required></div><div class="form-group"><label>Message (optional)</label><textarea name="message" rows="3"></textarea></div><button type="submit">Submit Offer</button></form></div>`;
    document.body.appendChild(overlay);
    overlay.querySelector('.close').onclick = () => overlay.remove();
    overlay.onclick = (e) => { if (e.target === overlay) overlay.remove(); };
    overlay.querySelector('#offerForm').onsubmit = async (e) => {
        e.preventDefault();
        const fd = new FormData();
        fd.append('product_id', productId);
        fd.append('offer_price', e.target.offer_price.value);
        fd.append('message', e.target.message.value);
        try {
            await fetchAPI('api/make_offer.php', { method: 'POST', body: fd });
            showToast('Offer sent!', 'success');
            overlay.remove();
        } catch(err) { showToast(err.message, 'error'); }
    };
};

window.showContactModal = function(productId) {
    const overlay = document.createElement('div');
    overlay.className = 'modal-overlay';
    overlay.innerHTML = `<div class="modal"><span class="close">&times;</span><h3>Contact Seller</h3><form id="msgForm"><div class="form-group"><label>Your Message</label><textarea name="message" rows="4" required></textarea></div><button type="submit">Send</button></form></div>`;
    document.body.appendChild(overlay);
    overlay.querySelector('.close').onclick = () => overlay.remove();
    overlay.onclick = (e) => { if (e.target === overlay) overlay.remove(); };
    overlay.querySelector('#msgForm').onsubmit = async (e) => {
        e.preventDefault();
        const fd = new FormData();
        fd.append('product_id', productId);
        fd.append('message', e.target.message.value);
        try {
            await fetchAPI('api/send_message.php', { method: 'POST', body: fd });
            showToast('Message sent', 'success');
            overlay.remove();
        } catch(err) { showToast(err.message, 'error'); }
    };
};

// ========== PAGE INITIALIZATION (SINGLE CALL ONLY) ==========
document.addEventListener('DOMContentLoaded', async () => {
    initTheme();
    const path = window.location.pathname;
    const pageName = path.split('/').pop() || 'index.html';
    
    if (pageName === 'index.html' || path === '/' || path.endsWith('/BAUST_resale/')) {
        // Load initial products
        await loadProducts('', '');
        
        // Initialize search functionality
        initSearch();
        
        // Load categories for filter
        try {
            const response = await fetch('api/get_categories.php');
            const cats = await response.json();
            const sel = document.getElementById('categorySelect');
            if (sel && cats.length) {
                sel.innerHTML = '<option value="">All Categories</option>';
                cats.forEach(c => { 
                    let opt = document.createElement('option'); 
                    opt.value = c.id; 
                    opt.textContent = c.name; 
                    sel.appendChild(opt); 
                });
            }
        } catch(e) {
            console.error('Failed to load categories:', e);
        }
    } 
    
    else if (pageName === 'product.html') {
        const id = new URLSearchParams(location.search).get('id');
        if (id) await loadProductDetail(id);
    } 
    else if (pageName === 'add-product.html') {
        await initProductForm();
    } 
    else if (pageName === 'my-products.html') {
        await loadMyProducts();
    } 
    else if (pageName === 'my-offers.html') {
        await loadMyOffers();
    } 
    else if (pageName === 'my-purchases.html') {
        await loadMyPurchases();
    } 
    else if (pageName === 'login.html') {
        initLogin();
    } 
    else if (pageName === 'register.html') {
        initRegister();
    }
    // messages.html handles itself
    
    await updateNavbar();
    ensureThemeToggle();
});