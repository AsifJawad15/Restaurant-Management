# Customer Management System - Class Diagram

## Class Structure Overview

```
┌─────────────────────────────────────────────────────────────────────────────────┐
│                           Customer Management System                             │
└─────────────────────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────────────────────┐
│                                Presentation Layer                               │
└─────────────────────────────────────────────────────────────────────────────────┘

┌─────────────────────┐
│   CustomerView      │
├─────────────────────┤
│ + renderIndex()     │
│ + renderDetails()   │
│ + renderHeader()    │
│ + renderAlerts()    │
│ + renderStats()     │
│ + renderFilters()   │
│ + renderTable()     │
│ + renderModal()     │
│ + renderFooter()    │
│ - formatPrice()     │
└─────────────────────┘

┌─────────────────────────────────────────────────────────────────────────────────┐
│                               Controller Layer                                  │
└─────────────────────────────────────────────────────────────────────────────────┘

┌─────────────────────┐
│ CustomerController  │
├─────────────────────┤
│ - customerService   │
│ - customerView      │
├─────────────────────┤
│ + index()           │
│ + delete()          │
│ + search()          │
│ + show()            │
│ + updateLoyalty()   │
│ + updateTier()      │
│ + export()          │
│ + ajax()            │
│ - getFilters()      │
│ - handleError()     │
│ - redirectToIndex() │
│ - sendJsonResponse()│
└─────────────────────┘

┌─────────────────────────────────────────────────────────────────────────────────┐
│                              Business Logic Layer                               │
└─────────────────────────────────────────────────────────────────────────────────┘

┌─────────────────────┐
│  CustomerService    │
├─────────────────────┤
│ - customerRepository│
│ - customerStats     │
├─────────────────────┤
│ + getCustomers()    │
│ + getCustomerById() │
│ + deleteCustomer()  │
│ + getStatistics()   │
│ + getLoyaltyTiers() │
│ + searchCustomers() │
│ + getCustomersByTier()│
│ + getTopSpending()  │
│ + updateLoyaltyPoints()│
│ + addLoyaltyPoints()│
│ + redeemLoyaltyPoints()│
│ + updateCustomerTier()│
│ + getActivitySummary()│
│ + exportCustomers() │
│ - exportToCsv()     │
└─────────────────────┘

┌─────────────────────┐
│   CustomerStats     │
├─────────────────────┤
│ - db                │
├─────────────────────┤
│ + getStatistics()   │
│ + getLoyaltyTierDistribution()│
│ + getCustomerGrowthStats()│
│ + getSpendingStatistics()│
│ + getActivityStatistics()│
│ + getTopPerformingCustomers()│
│ + getCustomerDemographics()│
│ + getRetentionStatistics()│
│ + getDashboardStatistics()│
│ - getDateFormatForPeriod()│
│ - getIntervalDaysForPeriod()│
└─────────────────────┘

┌─────────────────────────────────────────────────────────────────────────────────┐
│                               Data Access Layer                                 │
└─────────────────────────────────────────────────────────────────────────────────┘

┌─────────────────────┐
│ CustomerRepository  │
├─────────────────────┤
│ - db                │
├─────────────────────┤
│ + getCustomersWithFilters()│
│ + findById()        │
│ + deleteCustomer()  │
│ + searchCustomers() │
│ + getCustomersByTier()│
│ + getTopSpendingCustomers()│
│ + getCustomerActivitySummary()│
│ + getCustomerCountByDateRange()│
│ + getCustomersPaginated()│
│ + getTotalCustomerCount()│
│ - buildSortClause() │
└─────────────────────┘

┌─────────────────────────────────────────────────────────────────────────────────┐
│                                Model Layer                                      │
└─────────────────────────────────────────────────────────────────────────────────┘

┌─────────────────────┐
│    BaseModel        │
├─────────────────────┤
│ # db                │
│ # table             │
│ # primaryKey        │
│ # fillable          │
│ # hidden            │
│ # casts             │
│ # attributes        │
│ # original          │
│ # exists            │
├─────────────────────┤
│ + fill()            │
│ + fillFromDatabase()│
│ + setAttribute()    │
│ + getAttribute()    │
│ + save()            │
│ + delete()          │
│ + find()            │
│ + all()             │
│ + create()          │
│ + validate()        │
│ + toArray()         │
│ + toJson()          │
│ + orderBy()         │
│ + get()             │
│ - isFillable()      │
│ - castAttribute()   │
│ - getAttributesForStorage()│
│ - prepareValueForStorage()│
│ - performInsert()   │
│ - performUpdate()   │
└─────────────────────┘

┌─────────────────────┐
│       User          │
├─────────────────────┤
│ # table: 'users'   │
│ # primaryKey: 'id'  │
│ # fillable: [...]   │
│ # hidden: [...]     │
│ # casts: [...]      │
├─────────────────────┤
│ + setPassword()     │
│ + verifyPassword()  │
│ + getFullName()     │
│ + isActive()        │
│ + isAdmin()         │
│ + isStaff()         │
│ + isCustomer()      │
│ + updateLastLogin() │
│ + findByEmail()     │
│ + findByUsername()  │
│ + getByType()       │
│ + validate()        │
│ - performInsert()   │
│ - performUpdate()   │
└─────────────────────┘

┌─────────────────────┐
│     Customer        │
├─────────────────────┤
│ # table: 'customer_profiles'│
│ # primaryKey: 'customer_id'│
│ # fillable: [...]   │
│ # casts: [...]      │
│ - user              │
├─────────────────────┤
│ + getUser()         │
│ + setUser()         │
│ + getFullName()     │
│ + getEmail()        │
│ + getAge()          │
│ + addLoyaltyPoints()│
│ + redeemLoyaltyPoints()│
│ + addPurchase()     │
│ + getPreferences()  │
│ + setPreferences() │
│ + addPreference()   │
│ + getPreference()   │
│ + findByUserId()    │
│ + getByTier()       │
│ + getTopSpenders()  │
│ + validate()        │
│ - updateTierLevel() │
│ - performInsert()   │
│ - performUpdate()   │
│ + toArray()         │
└─────────────────────┘

┌─────────────────────────────────────────────────────────────────────────────────┐
│                               Core Layer                                        │
└─────────────────────────────────────────────────────────────────────────────────┘

┌─────────────────────┐
│     Database        │
├─────────────────────┤
│ - instance          │
│ - connection        │
│ - config            │
│ - connectionPool    │
├─────────────────────┤
│ + getInstance()     │
│ + getConnection()   │
│ + query()           │
│ + fetchRow()        │
│ + fetchAll()        │
│ + insert()          │
│ + update()          │
│ + delete()          │
│ + beginTransaction()│
│ + commit()          │
│ + rollback()        │
│ + inTransaction()   │
│ - connect()         │
└─────────────────────┘

┌─────────────────────────────────────────────────────────────────────────────────┐
│                              Relationships                                      │
└─────────────────────────────────────────────────────────────────────────────────┘

CustomerController ──uses──> CustomerService
CustomerController ──uses──> CustomerView
CustomerService ──uses──> CustomerRepository
CustomerService ──uses──> CustomerStats
CustomerRepository ──uses──> Database
CustomerStats ──uses──> Database
CustomerView ──uses──> Customer (Model)
Customer ──extends──> User
User ──extends──> BaseModel
BaseModel ──uses──> Database

┌─────────────────────────────────────────────────────────────────────────────────┐
│                              Design Patterns                                    │
└─────────────────────────────────────────────────────────────────────────────────┘

1. MVC (Model-View-Controller) Pattern
   - Model: Customer, User, BaseModel
   - View: CustomerView
   - Controller: CustomerController

2. Repository Pattern
   - CustomerRepository abstracts data access

3. Service Layer Pattern
   - CustomerService handles business logic

4. Active Record Pattern
   - BaseModel implements ORM functionality

5. Singleton Pattern
   - Database class ensures single connection

6. Factory Pattern
   - Model creation through static methods

┌─────────────────────────────────────────────────────────────────────────────────┐
│                              Key Features                                       │
└─────────────────────────────────────────────────────────────────────────────────┘

• Separation of Concerns: Each layer has distinct responsibilities
• Dependency Injection: Services injected into controllers
• Error Handling: Centralized exception handling
• Data Validation: Model-level validation
• Statistics & Analytics: Dedicated stats service
• Export Functionality: CSV/JSON export capabilities
• AJAX Support: Asynchronous data loading
• Pagination: Large dataset handling
• Search & Filtering: Advanced query capabilities
• Loyalty Management: Points and tier system
• Activity Tracking: Customer behavior analytics
