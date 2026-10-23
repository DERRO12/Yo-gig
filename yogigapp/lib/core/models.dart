// Plain data classes for the JSON returned by the Laravel API.

double _double(dynamic value) => value is num ? value.toDouble() : double.tryParse('${value ?? ''}') ?? 0;

double? _doubleOrNull(dynamic value) => value == null ? null : _double(value);

int _int(dynamic value) => value is num ? value.toInt() : int.tryParse('${value ?? ''}') ?? 0;

String? _string(dynamic value) {
  if (value == null) {
    return null;
  }
  final text = value.toString();
  return text.isEmpty ? null : text;
}

DateTime? _date(dynamic value) => value == null ? null : DateTime.tryParse(value.toString())?.toLocal();

Map<String, dynamic> _map(dynamic value) => value is Map<String, dynamic> ? value : <String, dynamic>{};

List<T> listOf<T>(dynamic value, T Function(Map<String, dynamic>) parse) {
  if (value is Map && value['data'] is List) {
    value = value['data'];
  }
  if (value is! List) {
    return [];
  }
  return value.whereType<Map<String, dynamic>>().map(parse).toList();
}

Map<String, String> stringMap(dynamic value) {
  if (value is! Map) {
    return {};
  }
  return value.map((key, v) => MapEntry(key.toString(), v?.toString() ?? ''));
}

Map<String, int> countMap(dynamic value) {
  if (value is! Map) {
    return {};
  }
  return value.map((key, v) => MapEntry(key.toString(), _int(v)));
}

class MaidProfile {
  MaidProfile.fromJson(Map<String, dynamic> json)
      : verificationStatus = _string(json['verification_status']) ?? 'pending',
        isVerified = json['is_verified'] == true,
        nationalIdNumber = _string(json['national_id_number']),
        hasNationalIdPhoto = json['has_national_id_photo'] == true,
        bio = _string(json['bio']),
        serviceArea = _string(json['service_area']),
        available = json['available'] == true,
        completedJobs = _int(json['completed_jobs']),
        averageRating = _doubleOrNull(json['average_rating']),
        payoutMethod = _string(json['payout_method']),
        payoutMethodLabel = _string(json['payout_method_label']),
        payoutAccountName = _string(json['payout_account_name']),
        payoutAccountNumber = _string(json['payout_account_number']),
        hasPayoutDetails = json['has_payout_details'] == true;

  final String verificationStatus;
  final bool isVerified;
  final String? nationalIdNumber;
  final bool hasNationalIdPhoto;
  final String? bio;
  final String? serviceArea;
  final bool available;
  final int completedJobs;
  final double? averageRating;
  final String? payoutMethod;
  final String? payoutMethodLabel;
  final String? payoutAccountName;
  final String? payoutAccountNumber;
  final bool hasPayoutDetails;
}

class AppUser {
  AppUser.fromJson(Map<String, dynamic> json)
      : id = _int(json['id']),
        name = _string(json['name']) ?? '',
        email = _string(json['email']) ?? '',
        phoneNumber = _string(json['phone_number']) ?? '',
        role = _string(json['role']) ?? 'client',
        isActive = json['is_active'] != false,
        createdAt = _date(json['created_at']),
        maidProfile = json['maid_profile'] is Map<String, dynamic> ? MaidProfile.fromJson(json['maid_profile']) : null,
        bookingsAsClient = json['jobs_as_client_count'] == null ? null : _int(json['jobs_as_client_count']),
        bookingsAsMaid = json['jobs_as_maid_count'] == null ? null : _int(json['jobs_as_maid_count']);

  final int id;
  final String name;
  final String email;
  final String phoneNumber;
  final String role;
  final bool isActive;
  final DateTime? createdAt;
  final MaidProfile? maidProfile;
  final int? bookingsAsClient;
  final int? bookingsAsMaid;

  bool get isAdmin => role == 'admin';
  bool get isClient => role == 'client';
  bool get isMaid => role == 'maid';

  String get initials {
    final parts = name.trim().split(RegExp(r'\s+')).where((p) => p.isNotEmpty).toList();
    if (parts.isEmpty) {
      return '?';
    }
    return parts.take(2).map((p) => p[0].toUpperCase()).join();
  }

  String get roleLabel => switch (role) {
        'admin' => 'Super admin',
        'maid' => 'Maid',
        _ => 'Client',
      };
}

class Service {
  Service.fromJson(Map<String, dynamic> json)
      : id = _int(json['id']),
        name = _string(json['name']) ?? '',
        slug = _string(json['slug']) ?? '',
        category = _string(json['category']),
        shortDescription = _string(json['short_description']),
        description = _string(json['description']),
        imageUrl = _string(json['image_url']),
        basePrice = _double(json['base_price']),
        priceUnit = _string(json['price_unit']) ?? 'per visit',
        priceLabel = _string(json['price_label']),
        hasFixedPrice = json['has_fixed_price'] == true,
        isActive = json['is_active'] != false,
        isFeatured = json['is_featured'] == true,
        sortOrder = _int(json['sort_order']),
        bookingsCount = json['jobs_count'] == null ? null : _int(json['jobs_count']);

  final int id;
  final String name;
  final String slug;
  final String? category;
  final String? shortDescription;
  final String? description;
  final String? imageUrl;
  final double basePrice;
  final String priceUnit;
  final String? priceLabel;
  final bool hasFixedPrice;
  final bool isActive;
  final bool isFeatured;
  final int sortOrder;
  final int? bookingsCount;
}

class Payment {
  Payment.fromJson(Map<String, dynamic> json)
      : id = _int(json['id']),
        jobId = _int(json['job_id']),
        amount = _double(json['amount']),
        method = _string(json['method']) ?? '',
        methodLabel = _string(json['method_label']) ?? '',
        payerPhone = _string(json['payer_phone']),
        transactionReference = _string(json['transaction_reference']) ?? '',
        status = _string(json['status']) ?? 'pending',
        notes = _string(json['notes']),
        paidAt = _date(json['paid_at']),
        createdAt = _date(json['created_at']),
        booking = json['booking'] is Map<String, dynamic> ? Booking.fromJson(json['booking']) : null,
        client = json['client'] is Map<String, dynamic> ? AppUser.fromJson(json['client']) : null,
        verifier = json['verifier'] is Map<String, dynamic> ? AppUser.fromJson(json['verifier']) : null;

  final int id;
  final int jobId;
  final double amount;
  final String method;
  final String methodLabel;
  final String? payerPhone;
  final String transactionReference;
  final String status;
  final String? notes;
  final DateTime? paidAt;
  final DateTime? createdAt;
  final Booking? booking;
  final AppUser? client;
  final AppUser? verifier;

  bool get isPending => status == 'pending';
}

class Payout {
  Payout.fromJson(Map<String, dynamic> json)
      : id = _int(json['id']),
        jobId = _int(json['job_id']),
        amount = _double(json['amount']),
        method = _string(json['method']),
        methodLabel = _string(json['method_label']) ?? 'Not set',
        accountName = _string(json['account_name']),
        accountNumber = _string(json['account_number']),
        status = _string(json['status']) ?? 'pending',
        reference = _string(json['reference']),
        notes = _string(json['notes']),
        paidAt = _date(json['paid_at']),
        createdAt = _date(json['created_at']),
        booking = json['booking'] is Map<String, dynamic> ? Booking.fromJson(json['booking']) : null,
        maid = json['maid'] is Map<String, dynamic> ? AppUser.fromJson(json['maid']) : null;

  final int id;
  final int jobId;
  final double amount;
  final String? method;
  final String methodLabel;
  final String? accountName;
  final String? accountNumber;
  final String status;
  final String? reference;
  final String? notes;
  final DateTime? paidAt;
  final DateTime? createdAt;
  final Booking? booking;
  final AppUser? maid;

  bool get isPending => status == 'pending';
  bool get isPaid => status == 'paid';
}

/// A client booking; maids see the same record as a gig.
class Booking {
  Booking.fromJson(Map<String, dynamic> json)
      : id = _int(json['id']),
        reference = _string(json['reference']) ?? '',
        title = _string(json['title']) ?? 'Booking',
        description = _string(json['description']),
        status = _string(json['status']) ?? 'open',
        statusLabel = _string(json['status_label']) ?? '',
        paymentStatus = _string(json['payment_status']) ?? 'unpaid',
        paymentStatusLabel = _string(json['payment_status_label']) ?? '',
        scheduledDate = _string(json['scheduled_date']),
        scheduledTime = _string(json['scheduled_time']),
        scheduleLabel = _string(json['schedule_label']),
        location = _string(json['location']) ?? '',
        quantity = _int(json['quantity']),
        budget = _double(json['budget']),
        platformFee = _doubleOrNull(json['platform_fee']),
        maidEarnings = _doubleOrNull(json['maid_earnings']),
        imageUrls = (json['image_urls'] is List) ? (json['image_urls'] as List).map((e) => e.toString()).toList() : [],
        needsPayment = json['needs_payment'] == true,
        isFunded = json['is_funded'] == true,
        isAwaitingClientApproval = json['is_awaiting_client_approval'] == true,
        canBeCancelled = json['can_be_cancelled'] == true,
        clientRating = json['client_rating'] == null ? null : _int(json['client_rating']),
        clientReview = _string(json['client_review']),
        createdAt = _date(json['created_at']),
        service = json['service'] is Map<String, dynamic> ? Service.fromJson(json['service']) : null,
        client = json['client'] is Map<String, dynamic> ? AppUser.fromJson(json['client']) : null,
        maid = json['maid'] is Map<String, dynamic> ? AppUser.fromJson(json['maid']) : null,
        payments = listOf(json['payments'], Payment.fromJson),
        latestPayment = json['latest_payment'] is Map<String, dynamic> ? Payment.fromJson(json['latest_payment']) : null,
        payout = json['payout'] is Map<String, dynamic> ? Payout.fromJson(json['payout']) : null;

  final int id;
  final String reference;
  final String title;
  final String? description;
  final String status;
  final String statusLabel;
  final String paymentStatus;
  final String paymentStatusLabel;
  final String? scheduledDate;
  final String? scheduledTime;
  final String? scheduleLabel;
  final String location;
  final int quantity;
  final double budget;
  final double? platformFee;
  final double? maidEarnings;
  final List<String> imageUrls;
  final bool needsPayment;
  final bool isFunded;
  final bool isAwaitingClientApproval;
  final bool canBeCancelled;
  final int? clientRating;
  final String? clientReview;
  final DateTime? createdAt;
  final Service? service;
  final AppUser? client;
  final AppUser? maid;
  final List<Payment> payments;
  final Payment? latestPayment;
  final Payout? payout;

  bool get isOpen => status == 'open';
  bool get isMatched => status == 'matched';
  bool get isInProgress => status == 'in_progress';
  bool get isCompleted => status == 'completed';
  bool get isCancelled => status == 'cancelled';
}

class PageSection {
  PageSection.fromJson(Map<String, dynamic> json)
      : id = _int(json['id']),
        page = _string(json['page']) ?? 'home',
        section = _string(json['section']) ?? '',
        badge = _string(json['badge']),
        title = _string(json['title']),
        body = _string(json['body']),
        imageUrl = _string(json['image_url']),
        buttonText = _string(json['button_text']),
        buttonUrl = _string(json['button_url']),
        secondaryButtonText = _string(json['secondary_button_text']),
        secondaryButtonUrl = _string(json['secondary_button_url']),
        sortOrder = _int(json['sort_order']),
        isActive = json['is_active'] != false;

  final int id;
  final String page;
  final String section;
  final String? badge;
  final String? title;
  final String? body;
  final String? imageUrl;
  final String? buttonText;
  final String? buttonUrl;
  final String? secondaryButtonText;
  final String? secondaryButtonUrl;
  final int sortOrder;
  final bool isActive;
}

class Testimonial {
  Testimonial.fromJson(Map<String, dynamic> json)
      : id = _int(json['id']),
        name = _string(json['name']) ?? '',
        role = _string(json['role']),
        quote = _string(json['quote']) ?? '',
        rating = _int(json['rating']),
        photoUrl = _string(json['photo_url']),
        sortOrder = _int(json['sort_order']),
        isActive = json['is_active'] != false;

  final int id;
  final String name;
  final String? role;
  final String quote;
  final int rating;
  final String? photoUrl;
  final int sortOrder;
  final bool isActive;
}

class ContactMessage {
  ContactMessage.fromJson(Map<String, dynamic> json)
      : id = _int(json['id']),
        name = _string(json['name']) ?? '',
        email = _string(json['email']) ?? '',
        phone = _string(json['phone']),
        subject = _string(json['subject']),
        message = _string(json['message']) ?? '',
        readAt = _date(json['read_at']),
        createdAt = _date(json['created_at']);

  final int id;
  final String name;
  final String email;
  final String? phone;
  final String? subject;
  final String message;
  final DateTime? readAt;
  final DateTime? createdAt;

  bool get isUnread => readAt == null;
}

/// A website page: its content blocks grouped by section key, plus listings.
class PageContent {
  PageContent.fromJson(Map<String, dynamic> json)
      : sections = _sections(json['sections']),
        featuredServices = listOf(json['featured_services'], Service.fromJson),
        services = listOf(json['services'], Service.fromJson),
        freshGigs = listOf(json['fresh_gigs'], Booking.fromJson),
        totalOpenGigs = _int(json['total_open_gigs']),
        testimonials = listOf(json['testimonials'], Testimonial.fromJson);

  final Map<String, List<PageSection>> sections;
  final List<Service> featuredServices;
  final List<Service> services;
  final List<Booking> freshGigs;
  final int totalOpenGigs;
  final List<Testimonial> testimonials;

  List<PageSection> blocks(String key) => sections[key] ?? const [];

  PageSection? first(String key) => blocks(key).isEmpty ? null : blocks(key).first;

  static Map<String, List<PageSection>> _sections(dynamic value) {
    if (value is! Map) {
      return {};
    }
    return value.map((key, blocks) => MapEntry(key.toString(), listOf(blocks, PageSection.fromJson)));
  }
}

/// Site-wide settings managed by the super admin, plus labels for statuses and methods.
class SiteConfig {
  SiteConfig.fromJson(Map<String, dynamic> json)
      : settings = _map(json['settings']),
        paymentMethods = stringMap(json['payment_methods']),
        bookingStatuses = stringMap(json['booking_statuses']),
        paymentStatuses = stringMap(json['payment_statuses']),
        pages = _pages(json['pages']);

  SiteConfig.fallback()
      : settings = const {'site_name': 'Yo-gig House Manager', 'currency': 'UGX'},
        paymentMethods = const {
          'mtn_momo': 'MTN Mobile Money',
          'airtel_money': 'Airtel Money',
          'bank_transfer': 'Bank Transfer',
        },
        bookingStatuses = const {},
        paymentStatuses = const {},
        pages = const {};

  final Map<String, dynamic> settings;
  final Map<String, String> paymentMethods;
  final Map<String, String> bookingStatuses;
  final Map<String, String> paymentStatuses;
  final Map<String, Map<String, String>> pages;

  String text(String key, [String fallback = '']) => _string(settings[key]) ?? fallback;

  bool flag(String key) => settings[key] == true || settings[key] == '1';

  String get siteName => text('site_name', 'Yo-gig House Manager');
  String get tagline => text('site_tagline');
  String get currency => text('currency', 'UGX');
  String? get logoUrl => _string(settings['logo']);

  static Map<String, Map<String, String>> _pages(dynamic value) {
    if (value is! Map) {
      return {};
    }
    return value.map((key, sections) => MapEntry(key.toString(), stringMap(sections)));
  }
}

/// One editable field on the admin settings screen.
class SettingField {
  SettingField.fromJson(Map<String, dynamic> json)
      : key = _string(json['key']) ?? '',
        label = _string(json['label']) ?? '',
        type = _string(json['type']) ?? 'text',
        value = json['value']?.toString();

  final String key;
  final String label;
  final String type;
  final String? value;
}

class SettingGroup {
  SettingGroup.fromJson(Map<String, dynamic> json)
      : key = _string(json['key']) ?? '',
        label = _string(json['label']) ?? '',
        fields = listOf(json['fields'], SettingField.fromJson);

  final String key;
  final String label;
  final List<SettingField> fields;
}
