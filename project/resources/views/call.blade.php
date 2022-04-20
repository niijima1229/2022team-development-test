<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <!-- Fonts -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700&display=swap">

    <!-- Styles -->
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">

    <!-- Scripts -->
    <script src="{{ asset('js/app.js') }}" defer></script>
    <script src="https://cdn.webrtc.ecl.ntt.com/skyway-4.4.4.js"></script>
    <style>
        /* normalize */
        body {
            margin: 0;
        }

        /* global styles */
        video {
            background-color: #111;
            width: 100%;
        }

        .heading {
            text-align: center;
            margin-bottom: 0;
        }

        .note {
            text-align: center;
        }

        .meta {
            text-align: center;
            font-size: .8rem;
            color: gray;
        }

        .container {
            margin-left: auto;
            margin-right: auto;
            width: 980px;
        }

        /* p2p-media styles */
        .p2p-media {
            display: flex;
            align-items: center;
            flex-direction: column;
        }

        .p2p-media .remote-stream {
            width: 50%;
        }

        .p2p-media .local-stream {
            width: 30%;
        }

        /* p2p-data styles */
        .p2p-data {
            display: grid;
            grid-template-columns: 30% 1fr;
            margin: 0 8px;
        }

        .p2p-data .messages {
            background-color: #eee;
            min-height: 100px;
            padding: 8px;
            margin-top: 0;
        }

        /* room */
        .room {
            display: grid;
            grid-template-columns: 30% 40% 30%;
            gap: 8px;
            margin: 0 8px;
        }

        .room .remote-streams {
            background-color: #f6fbff;
        }

        .room .messages {
            background-color: #eee;
            min-height: 100px;
            padding: 8px;
            margin-top: 0;
        }

    </style>
    <title>Document</title>
</head>

<body>
    <div class="container">
        <h1 class="heading">P2P Media example</h1>
        <p class="note">
            Enter remote peer ID to call.
        </p>
        <div class="p2p-media">
            <div class="remote-stream">
                <video id="js-remote-stream"></video>
            </div>
            <div class="local-stream">
                <video id="js-local-stream"></video>
                <p>Your ID: <span id="js-local-id"></span></p>
                <input type="text" placeholder="Remote Peer ID" id="js-remote-id">
                <button id="js-call-trigger">Call</button>
                <button id="js-close-trigger">Close</button>
            </div>
        </div>
        <p class="meta" id="js-meta"></p>
    </div>
    <script>
        const Peer = window.Peer;

        (async function main() {
            const localVideo = document.getElementById('js-local-stream');
            const localId = document.getElementById('js-local-id');
            const callTrigger = document.getElementById('js-call-trigger');
            const closeTrigger = document.getElementById('js-close-trigger');
            const remoteVideo = document.getElementById('js-remote-stream');
            const remoteId = document.getElementById('js-remote-id');
            const meta = document.getElementById('js-meta');
            const sdkSrc = document.querySelector('script[src*=skyway]');

            meta.innerText = `
    UA: ${navigator.userAgent}
    SDK: ${sdkSrc ? sdkSrc.src : 'unknown'}
  `.trim();

            const localStream = await navigator.mediaDevices
                .getUserMedia({
                    audio: true,
                    video: false,
                })
                .catch(console.error);

            // Render local stream
            localVideo.muted = true;
            localVideo.srcObject = localStream;
            localVideo.playsInline = true;
            await localVideo.play().catch(console.error);

            const peer = (window.peer = new Peer({
                key: '{{ $key }}',
                debug: 3,
            }));

            // TODO:いずれユーザーごとに修正
            // const peer =  new Peer('ユーザーのPeerID', {
            //     key: '{{ $key }}',
            //     debug: 3,
            // });

            // Register caller handler
            callTrigger.addEventListener('click', () => {
                // Note that you need to ensure the peer has connected to signaling server
                // before using methods of peer instance.
                if (!peer.open) {
                    return;
                }

                const mediaConnection = peer.call(remoteId.value, localStream);

                mediaConnection.on('stream', async stream => {
                    // Render remote stream for caller
                    remoteVideo.srcObject = stream;
                    remoteVideo.playsInline = true;
                    await remoteVideo.play().catch(console.error);
                });

                mediaConnection.once('close', () => {
                    remoteVideo.srcObject.getTracks().forEach(track => track.stop());
                    remoteVideo.srcObject = null;
                });

                closeTrigger.addEventListener('click', () => mediaConnection.close(true));
            });

            peer.once('open', id => (localId.textContent = id));

            // Register callee handler
            peer.on('call', mediaConnection => {
                mediaConnection.answer(localStream);

                mediaConnection.on('stream', async stream => {
                    // Render remote stream for callee
                    remoteVideo.srcObject = stream;
                    remoteVideo.playsInline = true;
                    await remoteVideo.play().catch(console.error);
                });

                mediaConnection.once('close', () => {
                    remoteVideo.srcObject.getTracks().forEach(track => track.stop());
                    remoteVideo.srcObject = null;
                });

                closeTrigger.addEventListener('click', () => mediaConnection.close(true));
            });

            peer.on('error', console.error);
        })();
    </script>
</body>

</html>
