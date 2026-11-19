  import Alpine from 'alpinejs';
  import { start as startTurbo } from '@hotwired/turbo';
  import { initMasterBarangImport } from './master-barang-import.js';
  import { createRecordingGuIssue } from './recording-gu-issue.js';
  import { createRecordingGuReceipt } from './recording-gu-receipt.js';
  import { createStocktakeRow } from './stok-opname.js';

  window.Alpine = Alpine;
  Alpine.data('recordingGuReceipt', createRecordingGuReceipt);
  Alpine.data('recordingGuIssue', createRecordingGuIssue);
  Alpine.data('stocktakeRow', createStocktakeRow);
  Alpine.start();

  document.addEventListener('turbo:before-cache', () => {
    const layoutState = Alpine.$data(document.body);
    if (layoutState && layoutState.sidebarOpen !== undefined) {
      layoutState.sidebarOpen = false;
    }

    const importRoot = document.querySelector('#master-barang-import');
    if (importRoot) {
      delete importRoot.dataset.initialized;
    }

    Array.from(document.body.children).forEach((element) => {
      Alpine.destroyTree(element);
    });
  });

  document.addEventListener('turbo:load', () => {
    Alpine.initTree(document.body);

    initMasterBarangImport();

    if (document.querySelector('[data-admin-gu-dashboard]')) {
      import('./admin-gu-dashboard.js').then(({ mountAdminGUDashboard }) => {
        mountAdminGUDashboard();
      });
    }
  });

        document.addEventListener('submit', (event) => {
          if (event.target instanceof HTMLFormElement && event.target.method.toUpperCase() !== 'GET') {
            event.target.dataset.turbo = 'false';
          }
        }, true);

        startTurbo();
