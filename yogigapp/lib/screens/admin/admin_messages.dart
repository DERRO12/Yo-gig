import 'package:flutter/material.dart';

import '../../core/app_state.dart';
import '../../core/format.dart';
import '../../core/models.dart';
import '../../core/theme.dart';
import '../../widgets/common.dart';
import '../../widgets/paged_list.dart';
import '../public/site_links.dart';

class AdminMessagesScreen extends StatelessWidget {
  const AdminMessagesScreen({super.key});

  @override
  Widget build(BuildContext context) {
    final state = AppScope.of(context);

    return Scaffold(
      appBar: AppBar(title: const Text('Messages')),
      body: PagedList<ContactMessage>(
        fetch: (page) => state.api.get('/admin/messages', query: {'page': page}),
        parse: ContactMessage.fromJson,
        emptyMessage: 'No messages yet.',
        emptyIcon: Icons.mail_outline,
        itemBuilder: (context, message, reload) => Card(
          child: ListTile(
            leading: Icon(message.isUnread ? Icons.mark_email_unread : Icons.drafts_outlined, color: message.isUnread ? BrandColors.gold : BrandColors.sage),
            title: Text(message.subject ?? message.name, style: TextStyle(fontWeight: message.isUnread ? FontWeight.w800 : FontWeight.w500)),
            subtitle: Text('${message.name} · ${shortDate(message.createdAt)}\n${message.message}', maxLines: 2, overflow: TextOverflow.ellipsis),
            isThreeLine: true,
            onTap: () async {
              await Navigator.of(context).push(MaterialPageRoute(builder: (_) => MessageDetailScreen(messageId: message.id)));
              await reload();
            },
          ),
        ),
      ),
    );
  }
}

class MessageDetailScreen extends StatelessWidget {
  const MessageDetailScreen({super.key, required this.messageId});

  final int messageId;

  @override
  Widget build(BuildContext context) {
    final state = AppScope.of(context);

    return Scaffold(
      appBar: AppBar(title: const Text('Message')),
      body: AsyncView<ContactMessage>(
        load: () async => ContactMessage.fromJson((await state.api.get('/admin/messages/$messageId'))['data'] as Map<String, dynamic>),
        builder: (context, message, reload) => PageList(children: [
          Panel(children: [
            Text(message.subject ?? 'No subject', style: const TextStyle(fontSize: 18, fontWeight: FontWeight.w800)),
            const SizedBox(height: 8),
            InfoRow('From', message.name, icon: Icons.person_outline),
            InfoRow('Email', message.email, icon: Icons.email_outlined),
            if (message.phone != null) InfoRow('Phone', message.phone, icon: Icons.phone_outlined),
            InfoRow('Received', dateTime(message.createdAt), icon: Icons.schedule),
            const Divider(height: 24),
            SelectableText(message.message, style: const TextStyle(height: 1.5)),
          ]),
          const SizedBox(height: 12),
          Row(children: [
            Expanded(
              child: FilledButton.icon(
                onPressed: () => openExternal('mailto:${message.email}?subject=${Uri.encodeComponent('Re: ${message.subject ?? 'Your message'}')}'),
                icon: const Icon(Icons.reply),
                label: const Text('Reply by email'),
              ),
            ),
            if (message.phone != null) ...[
              const SizedBox(width: 10),
              IconButton.outlined(onPressed: () => openExternal('tel:${message.phone!.replaceAll(' ', '')}'), icon: const Icon(Icons.call)),
            ],
          ]),
          const SizedBox(height: 10),
          BusyButton(
            label: 'Delete message',
            icon: Icons.delete_outline,
            outlined: true,
            color: BrandColors.danger,
            onPressed: () async {
              if (await confirm(context, title: 'Delete this message?', message: 'This cannot be undone.', action: 'Delete', destructive: true) && context.mounted) {
                if (await runAction(context, () => state.api.delete('/admin/messages/$messageId')) && context.mounted) {
                  Navigator.pop(context);
                }
              }
            },
          ),
        ]),
      ),
    );
  }
}
