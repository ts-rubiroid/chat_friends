import 'dart:async';

import 'package:flutter/material.dart';
import 'package:just_audio/just_audio.dart';

class AudioPlayerBubble extends StatefulWidget {
  final String title;
  final String? url;
  final String? filePath;
  final bool dense;
  final Widget? trailing;

  const AudioPlayerBubble({
    super.key,
    required this.title,
    this.url,
    this.filePath,
    required this.dense,
    this.trailing,
  }) : assert(url != null || filePath != null, 'url или filePath обязателен');

  @override
  State<AudioPlayerBubble> createState() => _AudioPlayerBubbleState();
}

class _AudioPlayerBubbleState extends State<AudioPlayerBubble> {
  final AudioPlayer _player = AudioPlayer();

  StreamSubscription<Duration>? _posSub;
  StreamSubscription<Duration?>? _durSub;
  StreamSubscription<PlayerState>? _stateSub;

  Duration _position = Duration.zero;
  Duration _duration = Duration.zero;
  bool _loading = true;
  String? _error;
  bool _isPlaying = false;

  @override
  void initState() {
    super.initState();
    _init();
  }

  Future<void> _init() async {
    try {
      setState(() {
        _loading = true;
        _error = null;
      });

      if (widget.filePath != null) {
        await _player.setFilePath(widget.filePath!);
      } else {
        await _player.setUrl(widget.url!);
      }

      _posSub = _player.positionStream.listen((pos) {
        if (!mounted) return;
        setState(() {
          _position = pos;
        });
      });
      _durSub = _player.durationStream.listen((dur) {
        if (!mounted) return;
        setState(() {
          _duration = dur ?? Duration.zero;
        });
      });
      _stateSub = _player.playerStateStream.listen((state) {
        if (!mounted) return;
        setState(() {
          _isPlaying = state.playing;
        });
      });

      if (!mounted) return;
      setState(() {
        _loading = false;
      });
    } catch (e) {
      if (!mounted) return;
      setState(() {
        _loading = false;
        _error = e.toString();
      });
    }
  }

  @override
  void dispose() {
    _posSub?.cancel();
    _durSub?.cancel();
    _stateSub?.cancel();
    _player.dispose();
    super.dispose();
  }

  String _fmt(Duration d) {
    String two(int n) => n.toString().padLeft(2, '0');
    final m = d.inMinutes;
    final s = d.inSeconds % 60;
    return '${two(m)}:${two(s)}';
  }

  Future<void> _toggle() async {
    if (_loading || _error != null) return;
    try {
      if (_player.playing) {
        await _player.pause();
      } else {
        await _player.play();
      }
    } catch (_) {
      // ignore
    }
  }

  @override
  Widget build(BuildContext context) {
    final pad = widget.dense ? 10.0 : 12.0;
    final borderColor = widget.dense ? Colors.grey.shade300 : Colors.orange.withOpacity(0.25);

    return Container(
      padding: EdgeInsets.all(pad),
      decoration: BoxDecoration(
        color: Colors.orange.withOpacity(widget.dense ? 0.06 : 0.08),
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: borderColor),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Icon(Icons.audiotrack, color: Colors.orange.shade700),
              SizedBox(width: 8),
              Expanded(
                child: Text(
                  widget.title,
                  overflow: TextOverflow.ellipsis,
                  style: TextStyle(fontWeight: FontWeight.w600),
                ),
              ),
              if (widget.trailing != null) widget.trailing!,
            ],
          ),
          SizedBox(height: widget.dense ? 6 : 8),
          if (_error != null)
            Text(
              'Ошибка: $_error',
              style: TextStyle(color: Colors.red.shade700, fontSize: 12),
            )
          else
            Row(
              children: [
                IconButton(
                  onPressed: _loading ? null : _toggle,
                  icon: Icon(
                    _loading
                        ? Icons.hourglass_top
                        : _isPlaying
                            ? Icons.pause
                            : Icons.play_arrow,
                  ),
                ),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      SliderTheme(
                        data: SliderTheme.of(context).copyWith(
                          trackHeight: 2,
                          thumbShape: RoundSliderThumbShape(enabledThumbRadius: 6),
                        ),
                        child: Slider(
                          min: 0,
                          max: (_duration.inMilliseconds > 0)
                              ? _duration.inMilliseconds.toDouble()
                              : 1,
                          value: (_duration.inMilliseconds > 0)
                              ? _position.inMilliseconds.clamp(0, _duration.inMilliseconds).toDouble()
                              : 0,
                          onChanged: (_duration.inMilliseconds > 0)
                              ? (v) async {
                                  await _player.seek(Duration(milliseconds: v.toInt()));
                                }
                              : null,
                        ),
                      ),
                      Row(
                        mainAxisAlignment: MainAxisAlignment.spaceBetween,
                        children: [
                          Text(_fmt(_position), style: TextStyle(fontSize: 11, color: Colors.grey.shade700)),
                          Text(_fmt(_duration), style: TextStyle(fontSize: 11, color: Colors.grey.shade700)),
                        ],
                      ),
                    ],
                  ),
                ),
              ],
            ),
        ],
      ),
    );
  }
}

