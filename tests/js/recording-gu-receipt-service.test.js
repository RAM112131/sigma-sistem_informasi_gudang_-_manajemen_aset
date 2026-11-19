import assert from 'node:assert/strict';
import test from 'node:test';
import { createRecordingGuReceipt } from '../../resources/js/recording-gu-receipt.js';

function createReceiptService(overrides = {}) {
  return createRecordingGuReceipt('SHT', {
    csrfToken: 'test-token',
    storeRequests: [],
    directItemCatalog: [],
    ...overrides,
  });
}

test('calculates receipt differences and updates shortage state', () => {
  const service = createReceiptService();
  const item = {
    requestQty: 10,
    receivedQty: 8,
    damagedQty: 0,
    shortQty: 0,
    moreQty: 0,
    condition: 'Baik',
    matchesRequest: true,
  };

  assert.equal(service.storeRequestDifference(item), -2);
  service.updateStoreReceivedQuantity(item);

  assert.equal(item.shortQty, 2);
  assert.equal(item.moreQty, 0);
  assert.equal(item.condition, 'Kurang');
  assert.equal(item.matchesRequest, false);
  assert.equal(service.storeRequestStatus(item), 'Kurang dari permintaan');
});

test('normalizes damaged quantity to the received quantity', () => {
  const service = createReceiptService();
  const item = {
    receivedQty: 4,
    damagedQty: 7,
    requestQty: 4,
    condition: 'Baik',
    matchesRequest: true,
  };

  service.normalizeStoreDamagedQuantity(item);

  assert.equal(item.damagedQty, 4);
  assert.equal(item.condition, 'Rusak');
  assert.equal(item.matchesRequest, false);
});

test('filters store requests and keeps selection within the filter', () => {
  const service = createReceiptService({
    storeRequests: [
      { id: 'received', status: 'sudah_diterima', items: [] },
      { id: 'pending', status: 'menunggu_diterima', items: [] },
    ],
  });

  assert.equal(service.selectedStoreRequest.id, 'pending');
  service.setStoreRequestFilter('sudah_diterima');

  assert.deepEqual(service.filteredStoreRequests.map((request) => request.id), ['received']);
  assert.equal(service.selectedStoreRequestId, 'received');
});

test('formats stock previews and escapes highlighted item text', () => {
  const service = createReceiptService();

  assert.equal(service.stockQuantityPreview(5, 7), 0);
  assert.equal(service.formatReceiptQuantity(1234.5), '1.234,5');
  assert.equal(
    service.highlightDirectItem('<barang>', '<'),
    '<mark style="padding:0 1px;border-radius:2px;background:rgba(245,190,66,.38);color:inherit;">&lt;</mark>barang&gt;',
  );
});
