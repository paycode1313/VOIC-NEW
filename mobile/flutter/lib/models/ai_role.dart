class AiRole {
  final int id;
  final String name;
  final String roleType;
  final String difficultyLevel;
  final String? voiceId;
  final String description;
  final String? avatar;
  final List<String> personalityTraits;

  AiRole({
    required this.id,
    required this.name,
    required this.roleType,
    required this.difficultyLevel,
    this.voiceId,
    required this.description,
    this.avatar,
    required this.personalityTraits,
  });

  factory AiRole.fromJson(Map<String, dynamic> json) {
    return AiRole(
      id: json['id'] as int,
      name: json['name'] as String,
      roleType: json['role_type'] as String,
      difficultyLevel: json['difficulty_level'] as String? ?? 'Menengah',
      voiceId: json['voice_id'] as String?,
      description: json['description'] as String? ?? '',
      avatar: json['avatar'] as String?,
      personalityTraits: (json['personality_traits'] as List<dynamic>?)
              ?.map((e) => e.toString())
              .toList() ??
          [],
    );
  }
}

class PracticeScenario {
  final String id;
  final String name;
  final String icon;
  final String badge;
  final String badgeColor;
  final String roleType;
  final String description;
  final String promptGuide;
  final String targetDuration;

  PracticeScenario({
    required this.id,
    required this.name,
    required this.icon,
    required this.badge,
    required this.badgeColor,
    required this.roleType,
    required this.description,
    required this.promptGuide,
    required this.targetDuration,
  });

  factory PracticeScenario.fromJson(Map<String, dynamic> json) {
    return PracticeScenario(
      id: json['id'] as String,
      name: json['name'] as String,
      icon: json['icon'] as String? ?? '🎯',
      badge: json['badge'] as String? ?? 'Umum',
      badgeColor: json['badge_color'] as String? ?? 'indigo',
      roleType: json['role_type'] as String? ?? 'dosen_penguji',
      description: json['description'] as String? ?? '',
      promptGuide: json['prompt_guide'] as String? ?? '',
      targetDuration: json['target_duration'] as String? ?? '120 detik',
    );
  }
}
