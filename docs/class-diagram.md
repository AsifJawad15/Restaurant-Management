---
title: Restaurant Management System - Class Diagram
---
classDiagram
    %% Core Infrastructure Classes
    class Config {
        <<Singleton>>
        -Config instance
        -array config
        +getInstance() Config
        +get(string key, mixed default) mixed
        +set(string key, mixed value) void
        +has(string key) bool
        +all() array
    }

    class Database {
        <<Singleton>>
        -Database instance
        -PDO connection
        -Config config
        +getInstance() Database
        +getConnection() PDO
        +query(string sql, array params) PDOStatement
        +fetchRow(string sql, array params) array
        +fetchAll(string sql, array params) array
        +insert(string table, array data) string
        +update(string table, array data, array conditions) int
        +delete(string table, array conditions) int
        +beginTransaction() void
        +commit() void
        +rollback() void
    }

    %% Exception Classes
    class AppException {
        <<Exception>>
        #array context
        +getContext() array
        +setContext(array context) void
    }

    class DatabaseException {
        <<Exception>>
    }

    class AuthException {
        <<Exception>>
    }

    class ValidationException {
        <<Exception>>
        -array errors
        +getErrors() array
        +addError(string field, string message) void
    }

    %% Base Model Class
    class BaseModel {
        <<Abstract>>
        #Database db
        #string table
        #string primaryKey
        #array fillable
        #array hidden
        #array casts
        #array attributes
        #array original
        #bool exists
        +fill(array attributes) BaseModel
        +setAttribute(string key, mixed value) void
        +getAttribute(string key) mixed
        +save() bool
        +delete() bool
        +find(mixed id) BaseModel
        +all() array
        +create(array attributes) BaseModel
        #validate() void
        +toArray() array
        +toJson() string
    }

    %% Entity Models
    class User {
        #string table = "users"
        #string primaryKey = "user_id"
        +user_id : int
        +username : string
        +email : string
        +password : string
        +first_name : string
        +last_name : string
        +phone : string
        +user_type : string
        +is_active : bool
        +email_verified : bool
        +last_login : DateTime
        +created_at : DateTime
        +updated_at : DateTime
        +setPassword(string password) void
        +verifyPassword(string password) bool
        +getFullName() string
        +isActive() bool
        +isAdmin() bool
        +isStaff() bool
        +isCustomer() bool
        +updateLastLogin() void
        +findByEmail(string email) User
        +findByUsername(string username) User
        +getByType(string type) array
    }

    class Customer {
        #string table = "customer_profiles"
        #string primaryKey = "customer_id"
        +customer_id : int
        +user_id : int
        +date_of_birth : DateTime
        +preferences : array
        +loyalty_points : int
        +tier_level : string
        +total_spent : float
        +visit_count : int
        +last_visit : DateTime
        +created_at : DateTime
        +updated_at : DateTime
        -User user
        +getUser() User
        +setUser(User user) void
        +getFullName() string
        +getEmail() string
        +getAge() int
        +addLoyaltyPoints(int points) void
        +redeemLoyaltyPoints(int points) bool
        +addPurchase(float amount) void
        +getPreferences() array
        +setPreferences(array preferences) void
        +findByUserId(int userId) Customer
        +getByTier(string tier) array
        +getTopSpenders(int limit) array
    }

    class Category {
        #string table = "categories"
        #string primaryKey = "category_id"
        +category_id : int
        +name : string
        +description : string
        +image_url : string
        +display_order : int
        +is_active : bool
        +parent_category_id : int
        +created_at : DateTime
        +updated_at : DateTime
        -Category parentCategory
        -array subCategories
        -array menuItems
        +getParentCategory() Category
        +setParentCategory(Category category) void
        +getSubCategories() array
        +getMenuItems() array
        +getAvailableMenuItems() array
        +isActive() bool
        +hasParent() bool
        +hasSubCategories() bool
        +hasMenuItems() bool
        +getHierarchyPath() array
        +getBreadcrumb(string separator) string
        +getRootCategories() array
        +getActive() array
        +getByParent(int parentId) array
        +getTree() array
    }

    class MenuItem {
        #string table = "menu_items"
        #string primaryKey = "item_id"
        +item_id : int
        +name : string
        +description : string
        +category_id : int
        +price : float
        +image_url : string
        +ingredients : array
        +allergens : array
        +nutritional_info : array
        +preparation_time : int
        +is_available : bool
        +is_featured : bool
        +is_vegetarian : bool
        +is_vegan : bool
        +is_gluten_free : bool
        +spice_level : int
        +serving_size : string
        +calories : int
        +created_at : DateTime
        +updated_at : DateTime
        -Category category
        +getCategory() Category
        +setCategory(Category category) void
        +getFormattedPrice(string currency) string
        +getSpiceLevelText() string
        +getIngredients() array
        +setIngredients(array ingredients) void
        +addIngredient(string ingredient) void
        +removeIngredient(string ingredient) void
        +getAllergens() array
        +setAllergens(array allergens) void
        +addAllergen(string allergen) void
        +hasAllergen(string allergen) bool
        +isAvailable() bool
        +toggleAvailability() void
        +markAsFeatured() void
        +unmarkAsFeatured() void
        +getByCategory(int categoryId) array
        +getAvailable() array
        +getFeatured() array
        +search(string query) array
        +getByDietaryRequirements(array requirements) array
    }

    class Order {
        #string table = "orders"
        #string primaryKey = "order_id"
        +order_id : int
        +customer_id : int
        +table_number : int
        +staff_id : int
        +order_type : string
        +status : string
        +total_amount : float
        +tax_amount : float
        +discount_amount : float
        +payment_method : string
        +payment_status : string
        +special_instructions : string
        +estimated_time : int
        +completed_at : DateTime
        +created_at : DateTime
        +updated_at : DateTime
        -Customer customer
        -User staff
        -array orderItems
        +getCustomer() Customer
        +getStaff() User
        +getOrderItems() array
        +addItem(int itemId, int quantity, string notes) void
        +calculateTotal() void
        +updateStatus(string status) void
        +cancel(string reason) void
        +getByStatus(string status) array
        +getByCustomer(int customerId, int limit) array
        +getTodaysOrders() array
    }

    class Reservation {
        #string table = "reservations"
        #string primaryKey = "reservation_id"
        +reservation_id : int
        +customer_id : int
        +customer_name : string
        +customer_email : string
        +customer_phone : string
        +party_size : int
        +reservation_date : DateTime
        +reservation_time : DateTime
        +table_number : int
        +status : string
        +special_requests : string
        +notes : string
        +created_at : DateTime
        +updated_at : DateTime
        -Customer customer
        +getCustomer() Customer
        +getReservationDateTime() DateTime
        +isToday() bool
        +isPast() bool
        +isUpcoming() bool
        +getCustomerDisplayName() string
        +getCustomerEmail() string
        +updateStatus(string status) void
        +confirm() void
        +cancel(string reason) void
        +markSeated(int tableNumber) void
        +markNoShow() void
        +complete() void
        +hasConflicts() bool
        +getByDate(string date) array
        +getTodaysReservations() array
        +getByStatus(string status) array
        +getByCustomer(int customerId) array
        +getUpcoming(int hours) array
    }

    class Review {
        #string table = "reviews"
        #string primaryKey = "review_id"
        +review_id : int
        +customer_id : int
        +order_id : int
        +item_id : int
        +rating : int
        +review_text : string
        +is_verified : bool
        +is_featured : bool
        +staff_response : string
        +responded_at : DateTime
        +created_at : DateTime
        +updated_at : DateTime
        -Customer customer
        -Order order
        -MenuItem menuItem
        +getCustomer() Customer
        +getOrder() Order
        +getMenuItem() MenuItem
        +getCustomerName() string
        +getMenuItemName() string
        +getRatingStars() string
        +isPositive() bool
        +isNegative() bool
        +markAsVerified() void
        +markAsFeatured() void
        +addStaffResponse(string response) void
        +getVerified(int limit) array
        +getFeatured() array
        +getByRating(int rating) array
        +getForMenuItem(int itemId) array
        +getByCustomer(int customerId) array
        +getRecent(int days, int limit) array
        +getStatistics() array
        +getAverageRatingForItem(int itemId) float
    }

    %% Service Classes
    class AuthService {
        <<Service>>
        <<Singleton>>
        -AuthService instance
        +getInstance() AuthService
        +login(string emailOrUsername, string password) User
        +registerCustomer(array userData) Customer
        +logout() void
        +isLoggedIn() bool
        +getCurrentUser() User
        +getCurrentUserId() int
        +getCurrentUserType() string
        +isAdmin() bool
        +isStaff() bool
        +isCustomer() bool
        +getCurrentCustomer() Customer
        +requireAuth(string redirectUrl) void
        +requireAdmin(string redirectUrl) void
        +requireStaff(string redirectUrl) void
        +changePassword(string currentPassword, string newPassword) void
        +updateProfile(array data) User
        +generatePasswordResetToken(string email) string
        +resetPassword(string token, string newPassword) void
        +getSessionData() array
    }

    class OrderManager {
        <<Service>>
        +createOrder(int customerId, string orderType) Order
        +addItemToOrder(int orderId, int itemId, int quantity, string notes) void
        +calculateOrderTotal(int orderId) float
        +updateOrderStatus(int orderId, string status) void
        +completeOrder(int orderId) void
        +cancelOrder(int orderId, string reason) void
        +getOrdersByStatus(string status) array
        +getTodaysOrders() array
        +getCustomerOrders(int customerId, int limit) array
        +getOrderStatistics() array
    }

    class ReservationManager {
        <<Service>>
        +createReservation(array data) Reservation
        +updateReservation(int reservationId, array data) Reservation
        +cancelReservation(int reservationId, string reason) void
        +confirmReservation(int reservationId) void
        +seatReservation(int reservationId, int tableNumber) void
        +markNoShow(int reservationId) void
        +getReservationsByDate(string date) array
        +getTodaysReservations() array
        +getUpcomingReservations(int hours) array
        +getReservationsByStatus(string status) array
        +getCustomerReservations(int customerId) array
        +checkTableAvailability(string date, string time, int partySize) array
        +getReservationStatistics() array
        +autoConfirmReservations() int
        +markOverdueAsNoShow() int
    }

    class MenuService {
        <<Service>>
        -Database db
        -MenuRepository menuRepository
        +getAllMenuItems(array filters) array
        +getMenuItemsByCategory(int categoryId) array
        +getFeaturedItems() array
        +getPopularItems(int limit) array
        +createMenuItem(array data) MenuItem
        +updateMenuItem(int id, array data) bool
        +deleteMenuItem(int id) bool
        +toggleAvailability(int id) bool
        +updatePrice(int id, float price) bool
    }

    class CustomerService {
        <<Service>>
        -CustomerRepository customerRepository
        -CustomerStats customerStats
        +getAllCustomers(array filters, string sortBy) array
        +getCustomerById(int id) Customer
        +createCustomer(array data) Customer
        +updateCustomer(int id, array data) bool
        +deleteCustomer(int id) bool
        +getCustomerStats(int id) array
        +updateLoyaltyPoints(int id, int points) bool
        +getTopCustomers(int limit) array
    }

    class CategoryService {
        <<Service>>
        -Database db
        +getAllCategories() array
        +getActiveCategories() array
        +createCategory(array data) Category
        +updateCategory(int id, array data) bool
        +deleteCategory(int id) bool
        +toggleStatus(int id) bool
        +reorderCategories(array order) bool
    }

    %% Repository Classes
    class MenuRepository {
        <<Repository>>
        -Database db
        +findById(int id) MenuItem
        +findByCategory(int categoryId) array
        +findFeatured() array
        +findAvailable() array
        +search(string query) array
        +getPopular(int limit) array
        +create(array data) MenuItem
        +update(int id, array data) bool
        +delete(int id) bool
    }

    class CustomerRepository {
        <<Repository>>
        -Database db
        +findById(int id) Customer
        +findByEmail(string email) Customer
        +findAll(array filters) array
        +create(array data) Customer
        +update(int id, array data) bool
        +delete(int id) bool
        +getByLoyaltyTier(string tier) array
        +search(string query) array
    }

    class CustomerStats {
        <<Repository>>
        -Database db
        +getTotalSpent(int customerId) float
        +getOrderCount(int customerId) int
        +getAverageOrderValue(int customerId) float
        +getLastOrderDate(int customerId) string
        +getFavoriteItems(int customerId, int limit) array
        +getLoyaltyPointsHistory(int customerId) array
        +getVisitFrequency(int customerId) array
    }

    class CategoryRepository {
        <<Repository>>
        -Database db
        +findById(int id) Category
        +findAll() array
        +findActive() array
        +create(array data) Category
        +update(int id, array data) bool
        +delete(int id) bool
        +getWithItemCount() array
    }

    %% Controller Classes
    class BaseAdminController {
        <<Abstract>>
        <<Controller>>
        #AuthService authService
        #Response response
        +__construct()
        #requireAuth() void
        #getCurrentAdmin() User
        #validateInput(array data, array rules) array
        #jsonResponse(mixed data, int status) void
        #redirectWithMessage(string url, string message) void
    }

    class CustomerController {
        <<Controller>>
        -CustomerService customerService
        +index() void
        +show(int id) void
        +create() void
        +store(array data) void
        +edit(int id) void
        +update(int id, array data) void
        +destroy(int id) void
        +stats(int id) void
    }

    class MenuController {
        <<Controller>>
        -MenuService menuService
        +index() void
        +show(int id) void
        +create() void
        +store(array data) void
        +edit(int id) void
        +update(int id, array data) void
        +destroy(int id) void
        +toggleAvailability(int id) void
    }

    %% View Classes
    class CustomerView {
        <<View>>
        +renderCustomerList(array customers) string
        +renderCustomerProfile(Customer customer) string
        +renderCustomerStats(array stats) string
        +renderCustomerForm(Customer customer) string
        +renderOrderHistory(array orders) string
    }

    %% Helper Classes
    class Response {
        <<Utility>>
        +json(array data, int statusCode) void
        +success(string message, array data) void
        +error(string message, array errors, int statusCode) void
        +validationError(array errors, string message) void
        +notFound(string message) void
        +unauthorized(string message) void
        +forbidden(string message) void
        +redirect(string url, string message, string type) void
        +redirectBack(string message, string type, string default) void
        +getFlashMessage() array
        +setFlashMessage(string message, string type) void
    }

    class Validator {
        <<Utility>>
        -array data
        -array rules
        -array errors
        -array customMessages
        +rules(array rules) Validator
        +messages(array messages) Validator
        +validate() bool
        +getErrors() array
        +fails() bool
        +validateOrFail() void
        +make(array data, array rules, array messages) Validator
        +quick(array data, array rules, array messages) bool
    }

    %% Relationships - Inheritance
    BaseModel <|-- User
    BaseModel <|-- Customer
    BaseModel <|-- Category
    BaseModel <|-- MenuItem
    BaseModel <|-- Order
    BaseModel <|-- Reservation
    BaseModel <|-- Review

    AppException <|-- DatabaseException
    AppException <|-- AuthException
    AppException <|-- ValidationException

    %% Relationships - Composition and Associations
    Database *-- BaseModel : uses
    Config *-- Database : configures

    Customer o-- User : extends profile
    MenuItem o-- Category : belongs to
    Order *-- Customer : placed by
    Order o-- User : handled by
    Reservation o-- Customer : made by
    Review *-- Customer : written by
    Review o-- Order : for order
    Review o-- MenuItem : about item

    %% Service Dependencies
    AuthService ..> User : manages
    AuthService ..> Customer : registers
    OrderManager ..> Order : manages
    OrderManager ..> Customer : serves
    OrderManager ..> MenuItem : uses
    ReservationManager ..> Reservation : manages
    ReservationManager ..> Customer : serves
    MenuService ..> MenuRepository : uses
    MenuService ..> MenuItem : manages
    CustomerService ..> CustomerRepository : uses
    CustomerService ..> CustomerStats : uses
    CustomerService ..> Customer : manages
    CategoryService ..> CategoryRepository : uses
    CategoryService ..> Category : manages

    %% Repository Dependencies
    MenuRepository ..> MenuItem : stores
    CustomerRepository ..> Customer : stores
    CustomerStats ..> Customer : analyzes
    CategoryRepository ..> Category : stores

    %% Controller Dependencies
    BaseAdminController <|-- CustomerController
    BaseAdminController <|-- MenuController
    BaseAdminController ..> AuthService : uses
    BaseAdminController ..> Response : uses
    CustomerController ..> CustomerService : uses
    MenuController ..> MenuService : uses

    %% View Dependencies
    CustomerView ..> Customer : renders

    %% Utility Dependencies
    BaseModel ..> Validator : validates with
    AuthService ..> Response : responds with
    OrderManager ..> Response : responds with
    ReservationManager ..> Response : responds with

    %% Notes
    note for BaseModel "Active Record Pattern\nProvides CRUD operations\nfor all models"
    note for Database "Singleton Pattern\nManages PDO connections\nProvides query methods"
    note for Config "Singleton Pattern\nCentralized configuration\nmanagement"
    note for AuthService "Handles authentication\nsession management\nand user registration"

    %% Styling
    classDef model fill:#e1f5fe,stroke:#01579b,stroke-width:2px
    classDef service fill:#f3e5f5,stroke:#4a148c,stroke-width:2px
    classDef repository fill:#f1f8e9,stroke:#33691e,stroke-width:2px
    classDef controller fill:#fff3e0,stroke:#e65100,stroke-width:2px
    classDef view fill:#ede7f6,stroke:#311b92,stroke-width:2px
    classDef core fill:#fff3e0,stroke:#e65100,stroke-width:2px
    classDef utility fill:#e8f5e8,stroke:#1b5e20,stroke-width:2px
    classDef exception fill:#ffebee,stroke:#c62828,stroke-width:2px

    class User:::model
    class Customer:::model
    class Category:::model
    class MenuItem:::model
    class Order:::model
    class Reservation:::model
    class Review:::model
    class BaseModel:::model
    class AuthService:::service
    class OrderManager:::service
    class ReservationManager:::service
    class MenuService:::service
    class CustomerService:::service
    class CategoryService:::service
    class MenuRepository:::repository
    class CustomerRepository:::repository
    class CustomerStats:::repository
    class CategoryRepository:::repository
    class BaseAdminController:::controller
    class CustomerController:::controller
    class MenuController:::controller
    class CustomerView:::view
    class Config:::core
    class Database:::core
    class Response:::utility
    class Validator:::utility
    class AppException:::exception
    class DatabaseException:::exception
    class AuthException:::exception
    class ValidationException:::exception
