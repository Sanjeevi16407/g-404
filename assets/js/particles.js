/**
 * Buddy 3D Particle Companion (Three.js WebGL Core Upgrade)
 * Constructs a real-time 3D particle sphere using WebGL Points,
 * featuring an internal head core and blinking circular eyes that track 
 * cursor coordinates via parallax gaze.
 */
class Buddy3DParticles {
    constructor(canvasId) {
        this.canvas = document.getElementById(canvasId);
        if (!this.canvas || typeof THREE === 'undefined') return;

        this.width = this.canvas.clientWidth;
        this.height = this.canvas.clientHeight;

        // 1. Initialize WebGL Scene
        this.scene = new THREE.Scene();
        
        // 2. Camera Setup (Z-Depth adjusted for fits)
        this.camera = new THREE.PerspectiveCamera(60, this.width / this.height, 0.1, 1000);
        this.camera.position.z = 12;

        // 3. WebGL Alpha Renderer
        this.renderer = new THREE.WebGLRenderer({
            canvas: this.canvas,
            alpha: true,
            antialias: true
        });
        this.renderer.setSize(this.width, this.height);
        this.renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));

        // 4. Companion Root Group
        this.buddyGroup = new THREE.Group();
        this.scene.add(this.buddyGroup);

        this.targetRotationX = 0;
        this.targetRotationY = 0;
        this.currentRotationX = 0;
        this.currentRotationY = 0;

        this.initParticles();
        this.initFace();
        this.addEventListeners();
        this.animate();
    }

    initParticles() {
        const particleCount = 280;
        const radius = 5.2; // Sphere scale coordinates
        const geometry = new THREE.BufferGeometry();
        const positions = new Float32Array(particleCount * 3);

        for (let i = 0; i < particleCount; i++) {
            const theta = Math.acos(Math.random() * 2 - 1);
            const phi = Math.random() * Math.PI * 2;

            positions[i * 3] = radius * Math.sin(theta) * Math.cos(phi);
            positions[i * 3 + 1] = radius * Math.sin(theta) * Math.sin(phi);
            positions[i * 3 + 2] = radius * Math.cos(theta);
        }

        geometry.setAttribute('position', new THREE.BufferAttribute(positions, 3));

        // Point Material with white glowing particles
        const material = new THREE.PointsMaterial({
            color: 0xffffff,
            size: 0.28,
            transparent: true,
            opacity: 0.9,
            blending: THREE.AdditiveBlending
        });

        this.particleSphere = new THREE.Points(geometry, material);
        this.buddyGroup.add(this.particleSphere);
    }

    initFace() {
        // Create dark head core mesh matching Black Titanium
        const headGeo = new THREE.SphereGeometry(3.1, 32, 32);
        const headMat = new THREE.MeshBasicMaterial({
            color: 0x111214,
            transparent: true,
            opacity: 0.95
        });
        this.headMesh = new THREE.Mesh(headGeo, headMat);
        this.buddyGroup.add(this.headMesh);

        // Group to contain eyes for parallax offsets
        this.eyesGroup = new THREE.Group();
        this.eyesGroup.position.z = 2.5; // Offset in front of the head core
        this.buddyGroup.add(this.eyesGroup);

        // Circular Eye Geometry
        const eyeGeo = new THREE.CircleGeometry(0.28, 32);
        this.eyeMat = new THREE.MeshBasicMaterial({
            color: 0xf2f2f2,
            transparent: true,
            opacity: 0.95
        });
        
        // Left Eye
        this.leftEye = new THREE.Mesh(eyeGeo, this.eyeMat);
        this.leftEye.position.x = -0.7;
        this.leftEye.position.y = 0.1;
        this.eyesGroup.add(this.leftEye);

        // Right Eye
        this.rightEye = new THREE.Mesh(eyeGeo, this.eyeMat);
        this.rightEye.position.x = 0.7;
        this.rightEye.position.y = 0.1;
        this.eyesGroup.add(this.rightEye);


        // Small white pulsing central core
        const coreGeo = new THREE.SphereGeometry(1.0, 16, 16);
        const coreMat = new THREE.MeshBasicMaterial({
            color: 0xffffff,
            transparent: true,
            opacity: 0.5
        });
        this.pulseCore = new THREE.Mesh(coreGeo, coreMat);
        this.buddyGroup.add(this.pulseCore);
    }

    addEventListeners() {
        window.addEventListener('resize', () => {
            if (!this.canvas) return;
            this.width = this.canvas.clientWidth;
            this.height = this.canvas.clientHeight;
            
            this.camera.aspect = this.width / this.height;
            this.camera.updateProjectionMatrix();
            
            this.renderer.setSize(this.width, this.height);
        });

        window.addEventListener('mousemove', (e) => {
            const rect = this.canvas.getBoundingClientRect();
            const centerX = rect.left + rect.width / 2;
            const centerY = rect.top + rect.height / 2;
            
            // Adjust looking angles based on cursor offset
            this.targetRotationY = (e.clientX - centerX) * 0.0015;
            this.targetRotationX = (e.clientY - centerY) * 0.0015;
        });
    }

    animate() {
        requestAnimationFrame(() => this.animate());

        // Slow idle auto-spin
        this.particleSphere.rotation.y += 0.003;
        this.particleSphere.rotation.x += 0.001;

        // Dampen rotations smoothly
        this.currentRotationY += (this.targetRotationY - this.currentRotationY) * 0.08;
        this.currentRotationX += (this.targetRotationX - this.currentRotationX) * 0.08;
        
        this.buddyGroup.rotation.y = this.currentRotationY;
        this.buddyGroup.rotation.x = this.currentRotationX;

        // Natural slow breathing scale loop
        const time = Date.now();
        const breathingScale = 1 + Math.sin(time * 0.002) * 0.035;
        this.buddyGroup.scale.set(breathingScale, breathingScale, breathingScale);


        // Pulse central white core
        if (this.pulseCore) {
            const pulse = 0.6 + Math.sin(time * 0.005) * 0.25;
            this.pulseCore.scale.set(pulse, pulse, pulse);
            this.pulseCore.material.opacity = 0.4 + Math.sin(time * 0.005) * 0.2;
        }

        // Shift eyes slightly within face to create spatial gaze depth
        this.eyesGroup.position.x = this.currentRotationY * 0.65;
        this.eyesGroup.position.y = -this.currentRotationX * 0.65;

        // Blink loop (shrink eye height for 150ms every 4.5 seconds)
        const blinkCycle = time % 4500;
        const isBlinking = blinkCycle < 150;
        
        if (isBlinking) {
            this.leftEye.scale.y = 0.1;
            this.rightEye.scale.y = 0.1;
        } else {
            this.leftEye.scale.y = 1.0;
            this.rightEye.scale.y = 1.0;
        }

        this.renderer.render(this.scene, this.camera);
    }
}
