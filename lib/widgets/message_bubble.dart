import 'package:flutter/material.dart';

/// Рисует «пузырь» сообщения с хвостиком внизу.
/// [isFromMe] true — хвостик справа (исходящие), false — хвостик слева (входящие).
class BubblePainter extends CustomPainter {
  final Color color;
  final bool isFromMe;

  static const double _radius = 18;
  static const double _tailHeight = 8;
  static const double _tailWidth = 10;

  BubblePainter({required this.color, required this.isFromMe});

  @override
  void paint(Canvas canvas, Size size) {
    final paint = Paint()..color = color;
    final path = Path();
    final w = size.width;
    final h = size.height;

    if (isFromMe) {
      // Исходящие: тело пузыря, хвостик внизу справа
      final bodyRect = RRect.fromRectAndCorners(
        Rect.fromLTWH(0, 0, w, h - _tailHeight),
        topLeft: const Radius.circular(_radius),
        topRight: const Radius.circular(_radius),
        bottomLeft: const Radius.circular(_radius),
        bottomRight: Radius.zero,
      );
      path.addRRect(bodyRect);
      path.addPolygon([
        Offset(w - _tailWidth, h - _tailHeight),
        Offset(w, h - _tailHeight),
        Offset(w, h),
      ], true);
    } else {
      // Входящие: тело пузыря, хвостик внизу слева
      final bodyRect = RRect.fromRectAndCorners(
        Rect.fromLTWH(0, 0, w, h - _tailHeight),
        topLeft: const Radius.circular(_radius),
        topRight: const Radius.circular(_radius),
        bottomLeft: Radius.zero,
        bottomRight: const Radius.circular(_radius),
      );
      path.addRRect(bodyRect);
      path.moveTo(0, h - _tailHeight);
      path.lineTo(0, h);
      path.lineTo(_tailWidth, h - _tailHeight);
      path.close();
    }

    canvas.drawPath(path, paint);
  }

  @override
  bool shouldRepaint(covariant BubblePainter oldDelegate) {
    return oldDelegate.color != color || oldDelegate.isFromMe != isFromMe;
  }
}

/// Обёртка для контента сообщения с пузырём и хвостиком.
class MessageBubble extends StatelessWidget {
  final bool isFromMe;
  final Color color;
  final Widget child;

  static const double tailHeight = 8;
  static const double bubblePadding = 12;

  const MessageBubble({
    super.key,
    required this.isFromMe,
    required this.color,
    required this.child,
  });

  @override
  Widget build(BuildContext context) {
    return CustomPaint(
      painter: BubblePainter(color: color, isFromMe: isFromMe),
      child: Padding(
        padding: const EdgeInsets.fromLTRB(
          bubblePadding,
          bubblePadding,
          bubblePadding,
          bubblePadding + tailHeight,
        ),
        child: child,
      ),
    );
  }
}
