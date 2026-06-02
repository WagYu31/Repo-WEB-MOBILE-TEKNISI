// import 'package:flutter/material.dart';
// import 'package:video_player/video_player.dart';
//
// class VideoPlayerScreen extends StatefulWidget {
//   final String videoUrl;
//
//   const VideoPlayerScreen({Key? key, required this.videoUrl}) : super(key: key);
//
//   @override
//   _VideoPlayerScreenState createState() => _VideoPlayerScreenState();
// }
//
// class _VideoPlayerScreenState extends State<VideoPlayerScreen> {
//   late VideoPlayerController _controller;
//
//   @override
//   void initState() {
//     super.initState();
//     _controller = VideoPlayerController.networkUrl(
//       Uri.parse('https://grav-tech.com/jadwal-3/api/storage/app/videos/${widget.videoUrl}'),
//     )..initialize().then((_) {
//         setState(() {});
//       });
//   }
//
//   @override
//   void dispose() {
//     _controller.dispose();
//     super.dispose();
//   }
//
//   void _skipForward() {
//     final currentPosition = _controller.value.position;
//     final duration = _controller.value.duration;
//     final skip = Duration(seconds: 3);
//     if (currentPosition + skip < duration) {
//       _controller.seekTo(currentPosition + skip);
//     } else {
//       _controller.seekTo(duration);
//     }
//   }
//
//   void _skipBackward() {
//     final currentPosition = _controller.value.position;
//     final skip = Duration(seconds: 3);
//     if (currentPosition - skip > Duration.zero) {
//       _controller.seekTo(currentPosition - skip);
//     } else {
//       _controller.seekTo(Duration.zero);
//     }
//   }
//
//   @override
//   Widget build(BuildContext context) {
//     return Scaffold(
//       appBar: AppBar(
//         title: Text('Video Player'),
//       ),
//       body: Column(
//         children: [
//           Expanded(
//             child: Center(
//               child: _controller.value.isInitialized
//                   ? AspectRatio(
//                       aspectRatio: _controller.value.aspectRatio,
//                       child: VideoPlayer(_controller),
//                     )
//                   : CircularProgressIndicator(),
//             ),
//           ),
//           _controller.value.isInitialized
//               ? VideoProgressIndicator(_controller, allowScrubbing: true)
//               : Container(),
//           Row(
//             mainAxisAlignment: MainAxisAlignment.center,
//             children: [
//               IconButton(
//                 icon: Icon(Icons.replay_10),
//                 onPressed: _skipBackward,
//               ),
//               IconButton(
//                 icon: Icon(_controller.value.isPlaying
//                     ? Icons.pause
//                     : Icons.play_arrow),
//                 onPressed: () {
//                   setState(() {
//                     _controller.value.isPlaying
//                         ? _controller.pause()
//                         : _controller.play();
//                   });
//                 },
//               ),
//               IconButton(
//                 icon: Icon(Icons.forward_10),
//                 onPressed: _skipForward,
//               ),
//             ],
//           ),
//         ],
//       ),
//     );
//   }
// }
