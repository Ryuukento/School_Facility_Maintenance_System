# Inventory Replacement Workflow - Implementation Checklist

✅ **COMPLETED DELIVERABLES**

## 1. Business Process Documentation
📄 **File:** `INVENTORY_REPLACEMENT_FLOW.md`
- End-to-end business flow (8 steps)
- Status transitions
- Data model design
- Rollout plan (3 phases)
- Acceptance criteria
- Mermaid flow diagram

## 2. Database Schema
📦 **File:** `database/migrations/2026_04_04_000600_create_inventory_workflow_tables.php`
- ✅ Created: `inventory_transactions` (audit trail)
- ✅ Created: `report_inventory_allocations` (reserve/deploy tracking)
- ✅ Created: `restock_requests` (procurement workflow)
- ✅ Added: `items.reserved_quantity` (stock reservation field)
- ✅ Added: `items.reorder_level` (low-stock threshold)
- **Status:** Already migrated to database ✓

## 3. API Endpoints
🔌 **File:** `public/backend/api/inventory-workflow-api.php`
- ✅ `check_availability` - Check item stock status
- ✅ `reserve` - Reserve item for report
- ✅ `release` - Cancel reservation
- ✅ `deploy` - Execute replacement (reduce inventory)
- ✅ `return` - Process defective item return
- ✅ `dispose` - Complete allocation
- ✅ `allocation_status` - Get report allocations
- ✅ `transactions` - Audit trail query
- ✅ `restock_create` - Create procurement request
- ✅ `restock_list` - List pending restocks
- ✅ `restock_update` - Update restock status

## 4. API Documentation
📖 **File:** `INVENTORY_WORKFLOW_API_REFERENCE.md`
- Endpoint descriptions
- Request examples
- Response formats
- Integration examples (JavaScript)
- Error handling
- Audit trail details

---

## 🚀 QUICK START FOR DEV TEAM

### Phase 1: Verify Setup (Today)
```bash
# 1. Check database tables created
mysql -u root school_facility_maintenance -e "SHOW TABLES LIKE 'inventory%';"
mysql -u root school_facility_maintenance -e "SHOW TABLES LIKE 'report_inventory%';"
mysql -u root school_facility_maintenance -e "SHOW TABLES LIKE 'restock%';"

# 2. Verify items table updates
mysql -u root school_facility_maintenance -e "DESC items;"
# Look for: reserved_quantity, reorder_level columns

# 3. Test API
curl "http://localhost/School_Facility_Maintenance_System/laravel_app/public/backend/api/inventory-workflow-api.php?action=check_availability&item_id=1"
```

### Phase 2: UI Integration (Next)
**Recommended Priority:**
1. Add "Check Inventory" button to report detail page
2. Add allocation widget showing:
   - Available quantity
   - Reserved quantity
   - Option to reserve/release
3. Add action buttons:
   - "Reserve Replacement"
   - "Deploy When Ready"
   - "Mark Returned"
4. Show low-stock alerts in inventory page

### Phase 3: Workflow Completion (After UI)
1. Link report completion flow to inventory (required fields)
2. Auto-create restock when stock = 0
3. Add notifications for low-stock/out-of-stock events
4. Build audit/export reports

---

## 📋 SAMPLE WORKFLOW WALKTHROUGH

**Scenario:** Projector in Science Lab 101 is broken (Report #4)

### Step 1: Report Created
```
Staff creates report #4 for broken projector
Location: Science Lab 101
Priority: High
```

### Step 2: Check Inventory (Admin Action)
```
API: GET /inventory-workflow-api.php?action=check_availability&item_id=1
Response: 6 units available
Decision: OK to deploy replacement
```

### Step 3: Reserve Item (Admin Action)
```
API: POST /inventory-workflow-api.php?action=reserve
Body: {
  "report_id": 4,
  "item_id": 1,
  "room_id": 10,
  "quantity": 1
}
Result: Allocation ID 23 created
Stock: 6 on_hand, 1 reserved → 5 available
```

### Step 4: Deploy Replacement (Technician Action)
```
API: POST /inventory-workflow-api.php?action=deploy
Body: {
  "allocation_id": 23,
  "quantity": 1
}
Result: Item deployed to Science Lab 101
Stock: 5 on_hand, 0 reserved
Transaction: logged with type "deploy"
```

### Step 5: Old Projector Returned (Technician Action)
```
API: POST /inventory-workflow-api.php?action=return
Body: {
  "allocation_id": 23,
  "quantity": 1,
  "return_status": "damaged"
}
Result: New inventory item created (ID 7)
Name: "Projector Epson X51 (Returned)"
Status: damaged
Location: Science Lab 101
```

### Step 6: Complete Report
```
Report #4 marked "completed"
Replacement item: ID 1
Defective item: ID 7 (damaged)
Technician notes: Lens damage, needs repair
```

### Step 7: Admin Reviews Inventory
```
Inventory page shows:
- Item #1: 5 on_hand, reorder level 5 → LOW_STOCK alert
- Item #7: 1 damaged, awaiting repair
Auto-creates restock request for Item #1
```

---

## 🔍 KEY CONCEPTS

| Concept | Definition | Example |
|---------|-----------|---------|
| **Allocation** | Link between report, item, and room | Report #4 reserves Projector for Lab 101 |
| **Reserved Qty** | Items set aside but not yet deployed | 1 projector reserved = not available to others |
| **Deployed Qty** | Items actually used for replacement | 1 projector deployed = removed from count |
| **Transaction** | Audit record of every stock movement | `reserve`, `deploy`, `return`, `dispose` |
| **Restock** | Procurement request when stock low | Auto-created when qty ≤ reorder_level |
| **Defective Item** | Original broken unit logged back to inventory | Tracked for repair, recycling, or disposal |

---

## 🛠️ FILES REFERENCE

```
laravel_app/
├── INVENTORY_REPLACEMENT_FLOW.md .................. Business flow & process
├── INVENTORY_WORKFLOW_API_REFERENCE.md ........... API documentation
├── database/
│   └── migrations/
│       └── 2026_04_04_000600_create_inventory_workflow_tables.php
│           └── Contains: inventory_transactions, report_inventory_allocations, restock_requests
├── public/frontend/
│   └── pages/
│       └── inventory.php ......................... Inventory page (read-only currently)
└── public/backend/
    └── api/
        └── inventory-workflow-api.php ........... All workflow operations (reserve/deploy/return)
```

---

## 📞 TROUBLESHOOTING

**Q: Allocation not creating?**
- Check: item_id, report_id, room_id all exist in database
- Check: Sufficient stock available (on_hand > reserved_qty)

**Q: Stock not updating after deploy?**
- API should automatically:
  - Decrement quantity
  - Update reserved_quantity
  - Check reorder_level
  - Create transaction record
- Check database directly: `SELECT quantity, reserved_quantity FROM items WHERE id=1;`

**Q: How to check audit trail?**
- API: `GET /inventory-workflow-api.php?action=transactions&report_id=4`
- Or query: `SELECT * FROM inventory_transactions WHERE report_id=4 ORDER BY created_at DESC;`

**Q: How to cancel a reserve?**
- API: `POST /inventory-workflow-api.php?action=release` with allocation_id
- Status changes from `reserved` → `cancelled`
- Stock freed back to available pool

---

## ✨ NEXT PHASE FEATURES (Optional)

1. **Batch Replacement** - Reserve/deploy multiple items at once
2. **Serial Tracking** - Track equipment by serial number
3. **Warranty Integration** - Auto-print warranty for new item
4. **Cost Tracking** - Calculate cost of deployment + repair
5. **Preventive Replacement** - Schedule replacements by age/usage
6. **Multi-location** - Support transferring items between buildings
7. **Mobile App** - Quick replace/return from phone on-site

---

**Date Ready:** April 4, 2026  
**Migration Status:** ✅ Applied  
**API Status:** ✅ Ready to use  
**Documentation Status:** ✅ Complete  

For questions or issues, refer to `INVENTORY_WORKFLOW_API_REFERENCE.md` for endpoint details.
