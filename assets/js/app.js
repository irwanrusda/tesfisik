let lastTouchEnd = 0;
let lastTouchStart = 0;
document.addEventListener('touchstart', (event) => {
    if (event.touches.length > 1) event.preventDefault();
    const now = Date.now();
    if (now - lastTouchStart <= 300) event.preventDefault();
    lastTouchStart = now;
}, { passive: false, capture: true });
document.addEventListener('touchend', (event) => {
    const now = Date.now();
    if (now - lastTouchEnd <= 350) event.preventDefault();
    lastTouchEnd = now;
}, { passive: false, capture: true });
document.addEventListener('dblclick', (event) => event.preventDefault(), { passive: false, capture: true });
['gesturestart', 'gesturechange', 'gestureend'].forEach((name) => {
    document.addEventListener(name, (event) => event.preventDefault(), { passive: false, capture: true });
});

const menuToggle = document.getElementById('menuToggle');
const sidebar = document.getElementById('sidebar');
const sidebarBackdrop = document.getElementById('sidebarBackdrop');
function toggleSidebar(force) {
    if (!sidebar) return;
    const open = typeof force === 'boolean' ? force : !sidebar.classList.contains('open');
    sidebar.classList.toggle('open', open);
    sidebarBackdrop?.classList.toggle('open', open);
    document.body.classList.toggle('sidebar-open', open);
}
menuToggle?.addEventListener('click', () => toggleSidebar());
sidebarBackdrop?.addEventListener('click', () => toggleSidebar(false));
sidebar?.querySelectorAll('a').forEach((link) => link.addEventListener('click', () => toggleSidebar(false)));
window.addEventListener('pageshow', () => toggleSidebar(false));
window.addEventListener('resize', () => {
    if (window.innerWidth > 800) toggleSidebar(false);
});

document.querySelectorAll('[data-nav-accordion-trigger]').forEach((trigger) => {
    trigger.addEventListener('click', () => {
        const accordion = trigger.closest('[data-nav-accordion]');
        const isOpen = accordion?.classList.toggle('open') || false;
        trigger.setAttribute('aria-expanded', String(isOpen));
    });
});

document.querySelectorAll('[data-station-toggle]').forEach((trigger) => {
    trigger.addEventListener('click', () => {
        if (window.innerWidth > 680) return;
        const card = trigger.closest('[data-station-card]');
        const isOpen = card?.classList.toggle('expanded') || false;
        trigger.setAttribute('aria-expanded', String(isOpen));
    });
});

document.querySelectorAll('form[data-confirm]').forEach((form) => {
    form.addEventListener('submit', (event) => {
        if (!window.confirm(form.dataset.confirm || 'Lanjutkan?')) event.preventDefault();
    });
});

const autoRefresh = document.querySelector('[data-auto-refresh]');
if (autoRefresh) {
    const interval = Number(autoRefresh.dataset.autoRefresh) || 30000;
    let refreshing = false;

    async function refreshPageContent() {
        if (refreshing || document.hidden) return;
        refreshing = true;
        try {
            const response = await fetch(window.location.href, {
                credentials: 'same-origin',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                cache: 'no-store',
            });
            if (!response.ok) return;

            const documentCopy = new DOMParser().parseFromString(await response.text(), 'text/html');
            const updatedContent = documentCopy.querySelector('[data-auto-refresh]');
            if (updatedContent) {
                autoRefresh.replaceChildren(...Array.from(updatedContent.childNodes).map((node) => node.cloneNode(true)));
            }
        } catch (error) {
            // Keep the current screen intact when the connection is unavailable.
        } finally {
            refreshing = false;
        }
    }

    window.setInterval(refreshPageContent, interval);
}

const heightInput = document.querySelector('[data-height]');
const weightInput = document.querySelector('[data-weight]');
const bmiInput = document.querySelector('[data-bmi]');
function updateBmi() {
    if (!heightInput || !weightInput || !bmiInput) return;
    const height = Number(heightInput.value) / 100;
    const weight = Number(weightInput.value);
    bmiInput.value = height > 0 && weight > 0 ? (weight / (height * height)).toFixed(2) : '';
}
heightInput?.addEventListener('input', updateBmi);
weightInput?.addEventListener('input', updateBmi);

const bleepForm = document.querySelector('[data-bleep-form]');
const bleepProtocolShuttles = [0, 7, 8, 8, 9, 9, 10, 10, 11, 11, 11, 12, 12, 13, 13, 13, 14, 14, 15, 15, 16, 16];
const bleepLevelInput = bleepForm?.querySelector('[data-bleep-level]');
const bleepShuttleInput = bleepForm?.querySelector('[data-bleep-shuttle]');
let bleepAthleteValue;
function vo2maxAgeGroup(age) {
    if (age === null || age < 26) return '18-25';
    if (age < 36) return '26-35';
    if (age < 46) return '36-45';
    if (age < 56) return '46-55';
    if (age < 66) return '56-65';
    return '65+';
}
function vo2maxCategory(vo2max, gender, age) {
    const group = vo2maxAgeGroup(age);
    const norms = {
        L: {
            '18-25': [60, 52, 47, 42, 37, 30],
            '26-35': [56, 49, 43, 40, 35, 30],
            '36-45': [51, 43, 39, 35, 31, 26],
            '46-55': [45, 39, 36, 32, 29, 25],
            '56-65': [41, 36, 32, 30, 26, 22],
            '65+': [37, 33, 29, 26, 22, 20],
        },
        P: {
            '18-25': [56, 47, 42, 38, 33, 28],
            '26-35': [52, 45, 39, 35, 31, 26],
            '36-45': [45, 38, 34, 31, 27, 22],
            '46-55': [40, 34, 31, 28, 25, 20],
            '56-65': [37, 32, 28, 25, 22, 18],
            '65+': [32, 28, 25, 22, 19, 17],
        },
    };
    const [excellent, good, aboveAverage, average, belowAverage, poor] = norms[gender === 'P' ? 'P' : 'L'][group];
    if (vo2max > excellent) return 'Excellent';
    if (vo2max >= good) return 'Good';
    if (vo2max >= aboveAverage) return 'Above Average';
    if (vo2max >= average) return 'Average';
    if (vo2max >= belowAverage) return 'Below Average';
    if (vo2max >= poor) return 'Poor';
    return 'Very Poor';
}
function updateBleepMetrics() {
    if (!bleepForm || !bleepLevelInput || !bleepShuttleInput) return;
    let level = Math.min(21, Math.max(1, Number(bleepLevelInput.value) || 1));
    const maxShuttles = bleepProtocolShuttles[level];
    let shuttle = Math.min(maxShuttles, Math.max(0, Number(bleepShuttleInput.value) || 0));
    bleepLevelInput.value = level;
    bleepShuttleInput.value = shuttle;
    bleepShuttleInput.max = maxShuttles;
    const previousShuttles = bleepProtocolShuttles.slice(1, level).reduce((sum, value) => sum + value, 0);
    const completedShuttles = previousShuttles + shuttle;
    const speed = 8 + (0.5 * (level + (shuttle / maxShuttles)));
    const birthDate = bleepForm.querySelector('[data-bleep-birth-date]')?.value;
    const testDate = bleepForm.querySelector('[data-bleep-test-date]')?.value;
    const birth = birthDate ? new Date(`${birthDate}T00:00:00`) : null;
    const testedAt = testDate ? new Date(`${testDate}T00:00:00`) : null;
    let age = null;
    if (birth && testedAt && !Number.isNaN(birth.getTime()) && !Number.isNaN(testedAt.getTime())) {
        age = testedAt.getFullYear() - birth.getFullYear();
        if (testedAt.getMonth() < birth.getMonth() || (testedAt.getMonth() === birth.getMonth() && testedAt.getDate() < birth.getDate())) age--;
    }
    const validation = bleepForm.querySelector('[data-bleep-validation]');
    const submitButton = bleepForm.querySelector('button[type="submit"]');
    let validationMessage = '';
    if (!testDate) validationMessage = 'Tanggal tes wajib diisi.';
    else if (birth && testedAt < birth) validationMessage = 'Tanggal tes tidak boleh lebih awal dari tanggal lahir.';
    if (validation) {
        validation.textContent = validationMessage;
        validation.hidden = validationMessage === '';
    }
    const selectedAthleteValue = bleepForm.querySelector('[data-bleep-athlete-value]')?.value;
    if (submitButton) submitButton.disabled = validationMessage !== '' || !selectedAthleteValue;
    const levelShuttleScore = level + (shuttle / ((level * 0.4325) + 7.0048));
    const vo2max = (3.46 * levelShuttleScore) + 12.19;
    const selectedOption = Array.from(document.querySelectorAll('[data-bleep-athlete-option]')).find((option) => option.dataset.id === selectedAthleteValue);
    const gender = selectedOption?.dataset.gender || 'L';
    const categoryInput = bleepForm.querySelector('[data-bleep-category]');
    if (categoryInput) categoryInput.value = validationMessage ? '-' : vo2maxCategory(vo2max, gender, age);
    bleepForm.querySelector('[data-vo2max]').textContent = validationMessage ? '-' : vo2max.toFixed(1);
    bleepForm.querySelector('[data-bleep-speed]').textContent = speed.toFixed(2);
    bleepForm.querySelector('[data-total-shuttles]').textContent = completedShuttles;
    bleepForm.querySelector('[data-bleep-distance]').textContent = completedShuttles * 20;
    bleepForm.querySelector('[data-shuttle-limit]').textContent = `Maksimal ${maxShuttles} shuttle pada level ini`;
    document.querySelectorAll('[data-protocol-row]').forEach((row) => row.classList.toggle('active', Number(row.dataset.protocolRow) === level));
}
bleepForm?.querySelectorAll('[data-step]').forEach((button) => {
    button.addEventListener('click', () => {
        const direction = Number(button.dataset.direction);
        if (button.dataset.step === 'level') {
            bleepLevelInput.value = Number(bleepLevelInput.value || 1) + direction;
            bleepShuttleInput.value = 0;
        } else {
            const level = Number(bleepLevelInput.value || 1);
            const shuttle = Number(bleepShuttleInput.value || 0);
            const maxShuttles = bleepProtocolShuttles[level];
            if (direction > 0 && shuttle >= maxShuttles && level < 21) {
                bleepLevelInput.value = level + 1;
                bleepShuttleInput.value = 0;
            } else if (direction < 0 && shuttle <= 0 && level > 1) {
                bleepLevelInput.value = level - 1;
                bleepShuttleInput.value = bleepProtocolShuttles[level - 1];
            } else {
                bleepShuttleInput.value = shuttle + direction;
            }
        }
        updateBleepMetrics();
    });
});
bleepLevelInput?.addEventListener('input', () => {
    bleepShuttleInput.value = 0;
    updateBleepMetrics();
});
bleepShuttleInput?.addEventListener('input', () => {
    let level = Math.min(21, Math.max(1, Number(bleepLevelInput.value) || 1));
    let shuttle = Math.max(0, Number(bleepShuttleInput.value) || 0);
    while (level < 21 && shuttle > bleepProtocolShuttles[level]) {
        shuttle -= bleepProtocolShuttles[level];
        level++;
    }
    bleepLevelInput.value = level;
    bleepShuttleInput.value = Math.min(shuttle, bleepProtocolShuttles[level]);
    updateBleepMetrics();
});
bleepForm?.querySelector('[data-bleep-birth-date]')?.addEventListener('change', updateBleepMetrics);
bleepForm?.querySelector('[data-bleep-test-date]')?.addEventListener('change', updateBleepMetrics);
updateBleepMetrics();

const bleepAthleteDropdown = document.querySelector('[data-bleep-athlete-dropdown]');
const bleepAthleteSearch = document.querySelector('[data-bleep-athlete-search]');
bleepAthleteValue = document.querySelector('[data-bleep-athlete-value]');
const bleepAthleteOptions = Array.from(document.querySelectorAll('[data-bleep-athlete-option]'));
const bleepAthleteEmpty = document.querySelector('[data-bleep-athlete-empty]');
function filterBleepAthletes() {
    const keyword = bleepAthleteSearch?.value.trim().toLocaleLowerCase('id-ID') || '';
    let visible = 0;
    bleepAthleteOptions.forEach((option) => {
        const matches = option.dataset.name.toLocaleLowerCase('id-ID').includes(keyword) || option.dataset.sport.toLocaleLowerCase('id-ID').includes(keyword);
        option.hidden = !matches;
        if (matches) visible++;
    });
    if (bleepAthleteEmpty) bleepAthleteEmpty.hidden = visible > 0;
    bleepAthleteDropdown?.classList.add('open');
}
bleepAthleteSearch?.addEventListener('focus', filterBleepAthletes);
bleepAthleteSearch?.addEventListener('input', () => {
    if (bleepAthleteValue) bleepAthleteValue.value = '';
    filterBleepAthletes();
    updateBleepMetrics();
});
bleepAthleteOptions.forEach((option) => option.addEventListener('click', () => {
    bleepAthleteOptions.forEach((item) => item.setAttribute('aria-selected', 'false'));
    option.setAttribute('aria-selected', 'true');
    bleepAthleteValue.value = option.dataset.id;
    bleepAthleteSearch.value = `${option.dataset.name} - ${option.dataset.sport}`;
    const birthDateInput = bleepForm?.querySelector('[data-bleep-birth-date]');
    if (birthDateInput) birthDateInput.value = option.dataset.birthDate || '';
    bleepAthleteDropdown?.classList.remove('open');
    updateBleepMetrics();
}));
const selectedBleepAthlete = bleepAthleteOptions.find((option) => option.dataset.id === bleepAthleteValue?.value);
if (selectedBleepAthlete) bleepAthleteSearch.value = `${selectedBleepAthlete.dataset.name} - ${selectedBleepAthlete.dataset.sport}`;
document.addEventListener('click', (event) => {
    if (bleepAthleteDropdown && !bleepAthleteDropdown.contains(event.target)) bleepAthleteDropdown.classList.remove('open');
});
bleepForm?.addEventListener('submit', (event) => {
    const validation = bleepForm.querySelector('[data-bleep-validation]');
    if (!bleepAthleteValue?.value || (validation && !validation.hidden)) {
        event.preventDefault();
        bleepAthleteSearch?.setCustomValidity(!bleepAthleteValue?.value ? 'Pilih atlet dari hasil pencarian.' : 'Periksa data Bleep Test yang belum valid.');
        bleepAthleteSearch?.reportValidity();
        bleepAthleteDropdown?.classList.add('open');
    } else {
        bleepAthleteSearch?.setCustomValidity('');
    }
});

const athleteDropdown = document.querySelector('[data-athlete-dropdown]');
const athleteCombobox = document.querySelector('[data-athlete-combobox]');
const athleteValue = document.querySelector('[data-athlete-value]');
const athleteOptions = Array.from(document.querySelectorAll('[data-athlete-option]'));
const athleteEmpty = document.querySelector('[data-athlete-empty]');

function updateAthleteFields(option) {
    const gender = option?.dataset.gender || '';
    const values = {
        '[data-athlete-name]': option?.dataset.name || '',
        '[data-athlete-sport]': option?.dataset.sport || '',
        '[data-athlete-gender]': gender,
        '[data-athlete-gender-label]': gender === 'L' ? 'Laki-Laki' : gender === 'P' ? 'Perempuan' : '',
        '[data-athlete-status]': option?.dataset.status || '',
    };
    Object.entries(values).forEach(([selector, value]) => {
        const input = document.querySelector(selector);
        if (input) input.value = value;
    });
}

function openAthleteDropdown() {
    athleteDropdown?.classList.add('open');
    athleteCombobox?.setAttribute('aria-expanded', 'true');
}

function closeAthleteDropdown() {
    athleteDropdown?.classList.remove('open');
    athleteCombobox?.setAttribute('aria-expanded', 'false');
}

function filterAthletes() {
    const keyword = athleteCombobox?.value.trim().toLocaleLowerCase('id-ID') || '';
    let visible = 0;
    athleteOptions.forEach((option) => {
        const matches = option.dataset.name.toLocaleLowerCase('id-ID').includes(keyword)
            || option.dataset.sport.toLocaleLowerCase('id-ID').includes(keyword);
        option.hidden = !matches;
        if (matches) visible++;
    });
    if (athleteEmpty) athleteEmpty.hidden = visible > 0;
    openAthleteDropdown();
}

athleteCombobox?.addEventListener('focus', filterAthletes);
athleteCombobox?.addEventListener('input', () => {
    if (athleteValue) athleteValue.value = '';
    updateAthleteFields(null);
    filterAthletes();
});

athleteOptions.forEach((option) => {
    option.addEventListener('click', () => {
        athleteOptions.forEach((item) => item.setAttribute('aria-selected', 'false'));
        option.setAttribute('aria-selected', 'true');
        if (athleteValue) athleteValue.value = option.dataset.id;
        if (athleteCombobox) athleteCombobox.value = `${option.dataset.name} - ${option.dataset.sport}`;
        updateAthleteFields(option);
        closeAthleteDropdown();
    });
});

document.addEventListener('click', (event) => {
    if (athleteDropdown && !athleteDropdown.contains(event.target)) closeAthleteDropdown();
});

athleteDropdown?.closest('form')?.addEventListener('submit', (event) => {
    if (!athleteValue?.value) {
        event.preventDefault();
        athleteCombobox?.setCustomValidity('Pilih atlet dari hasil pencarian.');
        athleteCombobox?.reportValidity();
        openAthleteDropdown();
    } else {
        athleteCombobox?.setCustomValidity('');
    }
});

const selectedAthlete = athleteOptions.find((option) => option.dataset.id === athleteValue?.value);
if (selectedAthlete) {
    athleteCombobox.value = `${selectedAthlete.dataset.name} - ${selectedAthlete.dataset.sport}`;
    updateAthleteFields(selectedAthlete);
}

const photoInputs = Array.from(document.querySelectorAll('[data-photo-input]'));

function compressPhoto(file) {
    return new Promise((resolve, reject) => {
        if (!file.type.startsWith('image/')) {
            reject(new Error(`${file.name} bukan file gambar.`));
            return;
        }

        const image = new Image();
        const objectUrl = URL.createObjectURL(file);
        image.onload = () => {
            URL.revokeObjectURL(objectUrl);
            const maxDimension = 1600;
            const scale = Math.min(1, maxDimension / Math.max(image.naturalWidth, image.naturalHeight));
            const width = Math.max(1, Math.round(image.naturalWidth * scale));
            const height = Math.max(1, Math.round(image.naturalHeight * scale));
            const canvas = document.createElement('canvas');
            canvas.width = width;
            canvas.height = height;
            const context = canvas.getContext('2d');
            context.fillStyle = '#fff';
            context.fillRect(0, 0, width, height);
            context.drawImage(image, 0, 0, width, height);
            canvas.toBlob((blob) => {
                if (!blob) {
                    reject(new Error(`${file.name} gagal dikompresi.`));
                    return;
                }
                const baseName = file.name.replace(/\.[^.]+$/, '') || 'dokumentasi';
                resolve(new File([blob], `${baseName}.jpg`, { type: 'image/jpeg', lastModified: Date.now() }));
            }, 'image/jpeg', 0.78);
        };
        image.onerror = () => {
            URL.revokeObjectURL(objectUrl);
            reject(new Error(`${file.name} tidak dapat dibaca oleh browser.`));
        };
        image.src = objectUrl;
    });
}

function renderPhotoPreviews(input) {
    const photoPreview = input.closest('form')?.querySelector('[data-photo-preview]');
    if (!photoPreview) return;
    photoPreview.replaceChildren();
    Array.from(input.files || []).forEach((file) => {
        const card = document.createElement('div');
        card.className = 'photo-preview-card';
        const image = document.createElement('img');
        image.alt = file.name;
        image.src = URL.createObjectURL(file);
        image.addEventListener('load', () => URL.revokeObjectURL(image.src), { once: true });
        const caption = document.createElement('span');
        caption.textContent = `${file.name} · ${Math.ceil(file.size / 1024)} KB`;
        card.append(image, caption);
        photoPreview.append(card);
    });
}

photoInputs.forEach((input) => {
    input.addEventListener('change', async () => {
        const selectedFiles = Array.from(input.files || []);
        if (selectedFiles.length > 10) {
            input.value = '';
            window.alert('Maksimal 10 foto dapat diunggah dalam satu penyimpanan.');
            renderPhotoPreviews(input);
            return;
        }

        input.disabled = true;
        try {
            const compressedFiles = [];
            for (const file of selectedFiles) {
                compressedFiles.push(await compressPhoto(file));
            }
            const transfer = new DataTransfer();
            compressedFiles.forEach((file) => transfer.items.add(file));
            input.files = transfer.files;
            renderPhotoPreviews(input);
        } catch (error) {
            input.value = '';
            renderPhotoPreviews(input);
            window.alert(error.message || 'Foto gagal diproses.');
        } finally {
            input.disabled = false;
        }
    });
});
