import 'package:flutter/material.dart';
import '../core/theme/app_theme.dart';

class CustomButton extends StatelessWidget {
  final String text;
  final VoidCallback? onPressed;
  final bool isLoading;
  final IconData? icon;
  final LinearGradient? gradient;
  final Color? backgroundColor;
  final Color textColor;
  final double height;
  final double borderRadius;

  const CustomButton({
    super.key,
    required this.text,
    this.onPressed,
    this.isLoading = false,
    this.icon,
    this.gradient,
    this.backgroundColor,
    this.textColor = Colors.white,
    this.height = 48,
    this.borderRadius = 14,
  });

  @override
  Widget build(BuildContext context) {
    final effectiveGradient = backgroundColor == null ? (gradient ?? AppTheme.primaryGradient) : null;

    return Container(
      height: height,
      decoration: BoxDecoration(
        gradient: onPressed == null ? null : effectiveGradient,
        color: onPressed == null
            ? AppTheme.borderDark.withValues(alpha: 0.4)
            : backgroundColor,
        borderRadius: BorderRadius.circular(borderRadius),
        boxShadow: onPressed != null && effectiveGradient != null
            ? [
                BoxShadow(
                  color: AppTheme.primaryIndigo.withValues(alpha: 0.3),
                  blurRadius: 12,
                  offset: const Offset(0, 4),
                )
              ]
            : null,
      ),
      child: Material(
        color: Colors.transparent,
        child: InkWell(
          onTap: isLoading ? null : onPressed,
          borderRadius: BorderRadius.circular(borderRadius),
          child: Center(
            child: isLoading
                ? const SizedBox(
                    width: 22,
                    height: 22,
                    child: CircularProgressIndicator(
                      strokeWidth: 2.5,
                      valueColor: AlwaysStoppedAnimation<Color>(Colors.white),
                    ),
                  )
                : Row(
                    mainAxisSize: MainAxisSize.min,
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      if (icon != null) ...[
                        Icon(icon, size: 18, color: textColor),
                        const SizedBox(width: 8),
                      ],
                      Text(
                        text,
                        style: TextStyle(
                          color: textColor,
                          fontSize: 14,
                          fontWeight: FontWeight.bold,
                          letterSpacing: 0.3,
                        ),
                      ),
                    ],
                  ),
          ),
        ),
      ),
    );
  }
}
