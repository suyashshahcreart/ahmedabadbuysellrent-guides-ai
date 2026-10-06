class MediaUpload {
    constructor() {
        this.uploadForm = document.getElementById('media-upload-form');
        this.dropzone = document.getElementById('upload-dropzone');
        this.progressContainer = document.getElementById('upload-progress');
        this.progressBar = document.getElementById('upload-progress-bar');
        this.statusText = document.getElementById('upload-status');
        this.fileList = document.getElementById('upload-file-list');
        this.selectedFiles = [];
        this.initializeEventListeners();
    }

    initializeEventListeners() {
        if (this.uploadForm) {
            this.uploadForm.addEventListener('submit', this.handleUpload.bind(this));
        }

        if (this.dropzone) {
            this.dropzone.addEventListener('dragover', this.handleDragOver.bind(this));
            this.dropzone.addEventListener('drop', this.handleDrop.bind(this));
            this.dropzone.addEventListener('click', this.handleClick.bind(this));
        }

        // Dismiss global error box
        const errorContainer = document.getElementById('uploadErrorContainer');
        if (errorContainer) {
            const closeBtn = errorContainer.querySelector('.btn-close');
            if (closeBtn) {
                closeBtn.addEventListener('click', function() {
                    errorContainer.style.display = 'none';
                });
            }
        }
    }

    handleDragOver(e) {
        e.preventDefault();
        e.stopPropagation();
        this.dropzone.classList.add('dragover');
    }

    handleDrop(e) {
        e.preventDefault();
        e.stopPropagation();
        this.dropzone.classList.remove('dragover');
        const files = e.dataTransfer.files;
        this.handleFiles(files);
    }

    handleClick() {
        const input = document.createElement('input');
        input.type = 'file';
        input.multiple = true;
        input.accept = '.jpeg,.jpg,.png,.gif,.webp,.avif,.mp4,.avi,.webm,.m4v,.mp3,.json,.xml,.sql';
        input.onchange = (e) => this.handleFiles(e.target.files);
        input.click();
    }

    handleFiles(files) {
        if (!files.length) return;
        // Remove previous error
        const errorContainer = document.getElementById('uploadErrorContainer');
        if (errorContainer) errorContainer.style.display = 'none';
        const allowedTypes = [
            'image/jpeg', 'image/png', 'image/jpg', 'image/gif', 'image/webp', 'image/avif',
            'image/svg+xml',
            'video/mp4', 'video/avi', 'video/webm', 'video/m4v',
            'audio/mp3', 'audio/webm',
            'application/json', 'application/xml', 'text/xml',
            'application/sql', 'text/sql',
            'application/pdf'
        ];
        const isAllowedSvg = (f) =>
            f.name.toLowerCase().endsWith('.svg') &&
            (!f.type || f.type === 'image/svg+xml');
        const maxSize = 1024 * 1024 * 1024; // 1GB in bytes
        const validFiles = [];
        const invalidFiles = [];
        Array.from(files).forEach(file => {
            if (!allowedTypes.includes(file.type) && !isAllowedSvg(file)) {
                invalidFiles.push({
                    file: file,
                    error: `File type "${file.type}" is not supported. Please upload only images, videos, audio, JSON, XML, SQL, or PDF files.`
                });
                return;
            }
            if (file.size > maxSize) {
                invalidFiles.push({
                    file: file,
                    error: `File size (${this.formatFileSize(file.size)}) exceeds the maximum allowed size of 1GB.`
                });
                return;
            }
            validFiles.push(file);
        });
        // Show errors for invalid files
        if (invalidFiles.length > 0) {
            const errorContainer = document.getElementById('uploadErrorContainer');
            const errorMessage = document.getElementById('uploadErrorMessage');
            if (errorContainer && errorMessage) {
                errorMessage.innerHTML = invalidFiles.map(item => 
                    `<strong>${item.file.name}:</strong> ${item.error}`
                ).join('<br>');
                errorContainer.style.display = 'block';
            }
        }
        // Only proceed with valid files
        if (validFiles.length > 0) {
            this.selectedFiles = validFiles;
            this.renderFileList();
            this.showProgress();
            this.uploadFiles();
        }
        // Always reset file input so selecting the same file again triggers change
        if (this.dropzone) {
            const fileInput = document.getElementById('uploadFile');
            if (fileInput) fileInput.value = '';
        }
    }

    renderFileList() {
        this.fileList.innerHTML = '';
        this.selectedFiles.forEach((file, idx) => {
            const fileItem = document.createElement('div');
            fileItem.className = 'upload-file-item';
            fileItem.setAttribute('data-index', idx);
            fileItem.style.position = 'relative';
            fileItem.innerHTML = `
                <div class="file-info">
                    <span class="filename" title="${file.name}">${file.name}</span>
                    <span class="filesize">${this.formatFileSize(file.size)}</span>
                </div>
                <div class="file-progress">
                    <div class="progress-bar">
                        <div class="progress" style="width: 0%"></div>
                    </div>
                    <span class="status">Waiting...</span>
                    <span class="status-icon"></span>
                </div>
                <button type="button" class="btn-close remove-file" aria-label="Remove" data-index="${idx}"> X </button>
                <div class="file-error-message" style="display:none;"></div>
            `;
            this.fileList.appendChild(fileItem);
        });

        // Add event delegation for remove-file
        if (this.fileList) {
            this.fileList.addEventListener('click', (e) => {
                if (e.target.classList.contains('remove-file')) {
                    const idx = parseInt(e.target.getAttribute('data-index'));
                    if (!isNaN(idx)) {
                        this.removeFile(idx);
                    }
                }
            });
        }
    }

    removeFile(index) {
        this.selectedFiles.splice(index, 1);
        this.renderFileList();
        if (this.selectedFiles.length === 0) {
            this.hideProgress();
        }
    }

    async uploadFiles() {
        console.log("Uploading Files", this.selectedFiles);
        for (let i = 0; i < this.selectedFiles.length; i++) {
            const file = this.selectedFiles[i];
            const formData = new FormData();
            formData.append('file', file);
            const fileItem = this.fileList.querySelector(`.upload-file-item[data-index="${i}"]`);
            const status = fileItem.querySelector('.status');
            const progressBar = fileItem.querySelector('.progress');
            const statusIcon = fileItem.querySelector('.status-icon');
            try {
                status.textContent = 'Uploading...';
                if (statusIcon) statusIcon.innerHTML = '';
                console.log("Form Data", formData);
                const response = await fetch(`${window.getAdminRouteBase()}/media`, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                console.log("Response", response);
                const contentType = response.headers.get('content-type');
                if (contentType && contentType.indexOf('application/json') === -1) {
                    throw new Error('The uploaded file is too large. Please select a smaller file.');
                }
                const data = await response.json();
                console.log("Data", data,"File Item", fileItem, "Response", response);
                if (!response.ok) {
                    let errorMessage = data.message || 'Upload failed';
                    if (data.errors && data.errors.file) {
                        // Store error details in dataset for later rendering
                        fileItem.dataset.errorDetails = JSON.stringify(data.errors.file);
                        errorMessage = data.errors.file;
                    }
                    this.showFileError(i, errorMessage);
                    continue;
                }
                // Success
                progressBar.style.width = '100%';
                fileItem.classList.add('success');
                status.textContent = 'Uploaded';
                if (statusIcon) {
                    statusIcon.innerHTML = '<i class="ri-checkbox-circle-line text-success"></i>';
                }
            } catch (error) {
                console.log("Error", error);
                this.showFileError(i, error.message);
            }
        }
    }

    showFileError(index, errorMessage) {
        alert(errorMessage + ' ' + index + ' ' + this.fileList);
        const fileItem = this.fileList.querySelector(`.upload-file-item[data-index="${index}"]`);
        console.log(fileItem, errorMessage);
        if (fileItem) {
            fileItem.classList.add('error');
            let errorDiv = fileItem.querySelector('.file-error-message');
            if (!errorDiv) {
                errorDiv = document.createElement('div');
                errorDiv.className = 'file-error-message';
                fileItem.appendChild(errorDiv);
            }
            errorDiv.style.display = 'block';
            // If errorMessage is an array, join with <br>
            if (Array.isArray(errorMessage)) {
                errorDiv.innerHTML = errorMessage.map(msg => `<span class='error-detail'>${msg}</span>`).join('<br>');
            } else if (typeof errorMessage === 'string' && errorMessage === 'Validation failed') {
                // Try to get details from the fileItem's dataset if available
                if (fileItem.dataset.errorDetails) {
                    const details = JSON.parse(fileItem.dataset.errorDetails);
                    errorDiv.innerHTML = details.map(msg => `<span class='error-detail'>${msg}</span>`).join('<br>');
                } else {
                    errorDiv.innerHTML = errorMessage;
                }
            } else {
                errorDiv.innerHTML = `<span class='error-detail'>${errorMessage}</span>`;
            }
            const status = fileItem.querySelector('.status');
            if (status) status.textContent = 'Error';
            // Add error icon
            const statusIcon = fileItem.querySelector('.status-icon');
            if (statusIcon) {
                statusIcon.innerHTML = '<i class="ri-close-circle-line text-danger"></i>';
            }
        }
        // Show global error box if any file fails
        const errorContainer = document.getElementById('uploadErrorContainer');
        const errorMessageDiv = document.getElementById('uploadErrorMessage');
        if (errorContainer && errorMessageDiv) {
            errorMessageDiv.innerHTML = 'Some files failed to upload. Please fix errors and try again.';
            errorContainer.style.display = 'block';
        }
    }

    showProgress() {
        this.progressContainer.style.display = 'block';
        this.dropzone.style.display = 'none';
    }

    hideProgress() {
        this.progressContainer.style.display = 'none';
        this.dropzone.style.display = 'block';
    }

    updateStatus(message) {
        this.statusText.textContent = message;
    }

    formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    new MediaUpload();
}); 