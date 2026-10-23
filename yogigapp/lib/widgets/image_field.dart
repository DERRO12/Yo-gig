import 'dart:typed_data';

import 'package:flutter/material.dart';
import 'package:image_picker/image_picker.dart';

import 'common.dart';

/// Shows the current image (or the newly picked one) with buttons to change or remove it.
class ImageField extends StatefulWidget {
  const ImageField({super.key, required this.label, this.currentUrl, required this.onPicked, this.onRemove});

  final String label;
  final String? currentUrl;
  final ValueChanged<XFile?> onPicked;
  final VoidCallback? onRemove;

  @override
  State<ImageField> createState() => _ImageFieldState();
}

class _ImageFieldState extends State<ImageField> {
  Uint8List? _preview;
  bool _removed = false;

  Future<void> _pick() async {
    final file = await ImagePicker().pickImage(source: ImageSource.gallery, maxWidth: 2000, imageQuality: 85);
    if (file == null) {
      return;
    }
    final bytes = await file.readAsBytes();
    setState(() {
      _preview = bytes;
      _removed = false;
    });
    widget.onPicked(file);
  }

  @override
  Widget build(BuildContext context) {
    final hasCurrent = widget.currentUrl != null && !_removed;

    return Column(crossAxisAlignment: CrossAxisAlignment.stretch, children: [
      Text(widget.label, style: const TextStyle(fontWeight: FontWeight.w600)),
      const SizedBox(height: 8),
      if (_preview != null)
        ClipRRect(borderRadius: BorderRadius.circular(12), child: Image.memory(_preview!, height: 160, fit: BoxFit.cover))
      else if (hasCurrent)
        NetImage(widget.currentUrl, height: 160)
      else
        Container(
          height: 90,
          alignment: Alignment.center,
          decoration: BoxDecoration(border: Border.all(color: Colors.black12), borderRadius: BorderRadius.circular(12)),
          child: const Text('No image'),
        ),
      const SizedBox(height: 8),
      Row(children: [
        Expanded(child: OutlinedButton.icon(onPressed: _pick, icon: const Icon(Icons.image_outlined), label: const Text('Choose image'))),
        if (widget.onRemove != null && (hasCurrent || _preview != null)) ...[
          const SizedBox(width: 8),
          TextButton(
            onPressed: () {
              setState(() {
                _preview = null;
                _removed = true;
              });
              widget.onPicked(null);
              widget.onRemove!();
            },
            child: const Text('Remove'),
          ),
        ],
      ]),
    ]);
  }
}
