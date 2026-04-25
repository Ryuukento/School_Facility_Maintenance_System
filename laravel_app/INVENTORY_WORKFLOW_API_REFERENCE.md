# Inventory Workflow API Reference

Base URL: `/School_Facility_Maintenance_System/laravel_app/public/backend/api/inventory-workflow-api.php`

## 1. Check Availability

**Action:** `check_availability`  
**Method:** GET or POST  
**Parameters:**
- `item_id` (required, int): Item ID from inventory

**Example:**
```
GET /...inventory-workflow-api.php?action=check_availability&item_id=1
```

**Response:**
```json
{
  "success": true,
  "data": {
    "item_id": 1,
    "name": "Projector Epson X51",
    "on_hand": 6,
    "reserved": 0,
    "available": 6,
    "reorder_level": 5,
    "status": "available"
  }
}
```

---

## 2. Reserve Item

**Action:** `reserve`  
**Method:** POST  
**Parameters:**
- `report_id` (required, int): Maintenance report ID
- `item_id` (required, int): Item ID to reserve
- `room_id` (required, int): Room where item will be deployed
- `quantity` (optional, int, default 1): Number of units to reserve

**Example:**
```json
{
  "report_id": 4,
  "item_id": 1,
  "room_id": 10,
  "quantity": 1
}
```

**Response:**
```json
{
  "success": true,
  "message": "Item reserved successfully",
  "data": {
    "allocation_id": 23,
    "report_id": 4,
    "item_id": 1,
    "quantity": 1
  }
}
```

---

## 3. Check Allocation Status

**Action:** `allocation_status`  
**Method:** GET  
**Parameters:**
- `report_id` (required, int): Maintenance report ID

**Example:**
```
GET /...inventory-workflow-api.php?action=allocation_status&report_id=4
```

**Response:**
```json
{
  "success": true,
  "data": {
    "report_id": 4,
    "allocations": [
      {
        "id": 23,
        "report_id": 4,
        "item_id": 1,
        "room_id": 10,
        "reserved_qty": 1,
        "deployed_qty": 0,
        "status": "reserved",
        "created_by": 5,
        "item_name": "Projector Epson X51",
        "quantity": 6,
        "room_name": "Science Lab 101"
      }
    ]
  }
}
```

---

## 4. Deploy Item (Execute Replacement)

**Action:** `deploy`  
**Method:** POST  
**Parameters:**
- `allocation_id` (required, int): Allocation ID from reserve operation
- `quantity` (optional, int): Quantity to deploy (default: full reserved amount)

**Example:**
```json
{
  "allocation_id": 23,
  "quantity": 1
}
```

**Response:**
```json
{
  "success": true,
  "message": "Item deployed successfully",
  "data": {
    "allocation_id": 23,
    "deployed_qty": 1
  }
}
```

**Side Effects:**
- Inventory quantity reduced by 1
- Stock status may update to `low_stock` or `out_of_stock` if threshold exceeded
- Transaction logged with type `deploy`

---

## 5. Return Item (Defective Unit)

**Action:** `return`  
**Method:** POST  
**Parameters:**
- `allocation_id` (required, int): Allocation ID
- `quantity` (required, int): Quantity to return
- `return_status` (optional, string, default "damaged"): Status of returned item
  - Options: `damaged`, `maintenance`, `for_disposal`, `for_repair`

**Example:**
```json
{
  "allocation_id": 23,
  "quantity": 1,
  "return_status": "damaged"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Item returned and logged",
  "data": {
    "returned_item_id": 7,
    "returned_qty": 1,
    "status": "damaged"
  }
}
```

**Side Effects:**
- Creates new inventory record for defective unit with status `damaged`
- New item name includes "(Returned)"
- Transaction logged with type `return`

---

## 6. Dispose Item (Mark Complete)

**Action:** `dispose`  
**Method:** POST  
**Parameters:**
- `allocation_id` (required, int): Allocation ID

**Example:**
```json
{
  "allocation_id": 23
}
```

**Response:**
```json
{
  "success": true,
  "message": "Item disposal logged",
  "data": {
    "allocation_id": 23
  }
}
```

---

## 7. Release Reservation (Cancel Reserve)

**Action:** `release`  
**Method:** POST  
**Parameters:**
- `allocation_id` (required, int): Allocation ID to cancel

**Example:**
```json
{
  "allocation_id": 23
}
```

**Response:**
```json
{
  "success": true,
  "message": "Reservation released",
  "data": {
    "allocation_id": 23
  }
}
```

**Side Effects:**
- Allocation status set to `cancelled`
- Item reserved_quantity decremented
- Transaction logged with type `release`

---

## 8. List Transactions (Audit Trail)

**Action:** `transactions`  
**Method:** GET  
**Parameters:**
- `item_id` (optional, int): Filter by item
- `report_id` (optional, int): Filter by report
- `limit` (optional, int, default 50): Result limit

**Example:**
```
GET /...inventory-workflow-api.php?action=transactions&report_id=4&limit=20
```

**Response:**
```json
{
  "success": true,
  "data": {
    "transactions": [
      {
        "id": 15,
        "item_id": 1,
        "report_id": 4,
        "room_id": 10,
        "transaction_type": "deploy",
        "quantity": 1,
        "reference_note": "Deployed to report #4 in room #10",
        "performed_by": 5,
        "created_at": "2026-04-04 14:30:00"
      }
    ]
  }
}
```

---

## 9. Create Restock Request

**Action:** `restock_create`  
**Method:** POST  
**Parameters:**
- `item_name` (required, string): Name of item needing restock
- `requested_qty` (required, int): Quantity requested
- `reason` (optional, string): Reason for request (e.g., "low_stock", "damaged")
- `priority` (optional, string, default "medium"): low|medium|high|urgent
- `source_report_id` (optional, int): Triggered by which maintenance report

**Example:**
```json
{
  "item_name": "Projector Epson X51",
  "requested_qty": 3,
  "reason": "Stock depleted after deployments",
  "priority": "high",
  "source_report_id": 4
}
```

**Response:**
```json
{
  "success": true,
  "message": "Restock request created",
  "data": {
    "id": 8
  }
}
```

---

## 10. List Restock Requests

**Action:** `restock_list`  
**Method:** GET  
**Parameters:**
- `status` (optional, string): Filter by status (open|approved|ordered|received|cancelled)
- `limit` (optional, int, default 50): Result limit

**Example:**
```
GET /...inventory-workflow-api.php?action=restock_list&status=open
```

**Response:**
```json
{
  "success": true,
  "data": {
    "restock_requests": [
      {
        "id": 8,
        "item_name": "Projector Epson X51",
        "requested_qty": 3,
        "reason": "Stock depleted after deployments",
        "source_report_id": 4,
        "priority": "high",
        "status": "open",
        "requested_by": 5,
        "approved_by": null,
        "created_at": "2026-04-04 14:35:00",
        "updated_at": "2026-04-04 14:35:00"
      }
    ]
  }
}
```

---

## 11. Update Restock Status

**Action:** `restock_update`  
**Method:** POST  
**Parameters:**
- `id` (required, int): Restock request ID
- `status` (required, string): New status (open|approved|ordered|received|cancelled)

**Example:**
```json
{
  "id": 8,
  "status": "approved"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Restock status updated to approved",
  "data": {
    "id": 8,
    "status": "approved"
  }
}
```

---

## Integration Example (JavaScript)

```javascript
// 1. Check availability when report is created
async function checkAndReserveItem(reportId, itemId, roomId) {
  // Check availability first
  const availResponse = await fetch(
    `/api/inventory-workflow-api.php?action=check_availability&item_id=${itemId}`
  );
  const availData = await availResponse.json();
  
  if (!availData.success || availData.data.available <= 0) {
    console.log('No stock available, create restock request');
    await createRestockRequest(itemId, 1, 'Required for report');
    return false;
  }

  // Reserve item
  const reserveResponse = await fetch(
    `/api/inventory-workflow-api.php?action=reserve`,
    {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        report_id: reportId,
        item_id: itemId,
        room_id: roomId,
        quantity: 1
      })
    }
  );
  
  const reserveData = await reserveResponse.json();
  console.log('Item reserved:', reserveData.data.allocation_id);
  return reserveData.data.allocation_id;
}

// 2. Deploy replacement
async function deployReplacement(allocationId) {
  const response = await fetch(
    `/api/inventory-workflow-api.php?action=deploy`,
    {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        allocation_id: allocationId,
        quantity: 1
      })
    }
  );
  const data = await response.json();
  console.log('Item deployed');
  return data.success;
}

// 3. Mark old item as damaged
async function returnDefectiveItem(allocationId) {
  const response = await fetch(
    `/api/inventory-workflow-api.php?action=return`,
    {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        allocation_id: allocationId,
        quantity: 1,
        return_status: 'damaged'
      })
    }
  );
  const data = await response.json();
  console.log('Defective item logged:', data.data.returned_item_id);
  return data.success;
}
```

---

## Error Responses

All errors follow this format:

```json
{
  "success": false,
  "message": "Description of error"
}
```

Common HTTP status codes:
- 200: Success
- 400: Bad request (missing/invalid parameters)
- 404: Not found
- 500: Server error

---

## Database Audit Trail

Every operation creates records in `inventory_transactions` table:
- `reserve` → qty reserved
- `release` → qty unreserved
- `deploy` → qty deployed (removed from on_hand)
- `return` → new item created with damaged status
- `dispose` → end of allocation
