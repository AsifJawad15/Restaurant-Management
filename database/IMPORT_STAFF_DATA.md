# Import Staff Sample Data

## Instructions

### Step 1: Open Command Prompt
Navigate to your XAMPP MySQL bin directory or use the full path

### Step 2: Import the SQL file
Run this command:

```cmd
d:\xampp\mysql\bin\mysql.exe -u root restaurant_management < database\staff_sample_data.sql
```

Or if you have password:
```cmd
d:\xampp\mysql\bin\mysql.exe -u root -p restaurant_management < database\staff_sample_data.sql
```

### Step 3: Verify
After import, you should see:
- ✓ Staff Profiles Table Created
- 10 staff members added
- Summary statistics by role

## What's Included

### Staff Members (10 total):
1. **Manager** - John Anderson (EMP001) - $65,000 - Active
2. **Head Chef** - Sarah Martinez (EMP002) - $48,000 - Active
3. **Sous Chef** - Michael Chen (EMP003) - $45,000 - Active
4. **Line Cook** - Sophia Rodriguez (EMP010) - $42,000 - Active
5. **Waiter** - Emma Johnson (EMP004) - $32,000 - Active
6. **Waiter** - David Williams (EMP005) - $30,000 - Active
7. **Waiter** - Lisa Brown (EMP006) - $31,000 - **Inactive** (on leave)
8. **Waiter** - James Wilson (EMP009) - $29,000 - Active
9. **Cashier** - Robert Taylor (EMP007) - $35,000 - Active
10. **Cleaner** - Maria Garcia (EMP008) - $28,000 - Active

### Default Password for All Staff:
**password** (hashed)

### Login Credentials:
You can login with any staff email (they're created as admin users):
- john.manager@restaurant.com
- sarah.chef@restaurant.com
- mike.chef@restaurant.com
- etc.

Password: **password**

## Important Notes:
- ✅ **Tables** already have sample data (6 tables: T01-T06)
- ✅ This uses your **existing** `staff` table structure
- ✅ Staff are created as 'admin' user_type (since your enum only has 'admin' and 'customer')
- ✅ Employee IDs: EMP001 through EMP010
