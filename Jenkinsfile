pipeline {
    agent any

    environment {
        REGISTRY  = 'ghcr.io'
        REPO      = 'ghcr.io/rha17369/intercity237'
        NAMESPACE = 'intercity237'
        SERVICES  = 'auth-service passenger-service route-service booking-service ticket-service notification-service api-gateway'
    }

    stages {

        // ── 1. CHECKOUT ──────────────────────────────────────────────
        stage('Checkout') {
            steps {
                checkout scm
                sh 'echo "Branch: ${GIT_BRANCH} — Build #${BUILD_NUMBER}"'
                sh 'git log --oneline -5'
            }
        }

        // ── 2. BUILD ALL SERVICES ─────────────────────────────────────
        stage('Build') {
            steps {
                script {
                    def services = env.SERVICES.split(' ')
                    services.each { svc ->
                        echo "Building ${svc}..."
                        sh "docker build -t ${REPO}/${svc}:${BUILD_NUMBER} -t ${REPO}/${svc}:latest ./${svc}"
                    }
                }
            }
        }

        // ── 3. UNIT TESTS + COVERAGE (auth-service) ───────────────────
        stage('Test') {
            steps {
                sh """
                    mkdir -p auth-service/coverage
                    docker run --rm \\
                      -v \$(pwd)/auth-service/coverage:/var/www/html/coverage \\
                      ${REPO}/auth-service:${BUILD_NUMBER} \\
                      bash -c "cd /var/www/html && \\
                               vendor/bin/phpunit \\
                                 --configuration phpunit.xml \\
                                 --coverage-text \\
                                 --coverage-html coverage/ 2>&1"
                """
            }
            post {
                always {
                    publishHTML(target: [
                        allowMissing: true,
                        alwaysLinkToLastBuild: true,
                        keepAll: true,
                        reportDir: 'auth-service/coverage',
                        reportFiles: 'index.html',
                        reportName: 'PHPUnit Coverage Report'
                    ])
                }
            }
        }

        // ── 4. QUALITY GATE (≥80% line coverage) ─────────────────────
        stage('Quality Gate') {
            steps {
                script {
                    def output = sh(
                        script: """
                            docker run --rm ${REPO}/auth-service:${BUILD_NUMBER} \\
                              bash -c "cd /var/www/html && vendor/bin/phpunit --coverage-text 2>&1" \\
                            | grep 'Lines:' | head -1
                        """,
                        returnStdout: true
                    ).trim()
                    echo "Coverage output: ${output}"
                    // Extract percentage (e.g. "Lines:  85.71% (12/14)")
                    def matcher = output =~ /Lines:\s+([\d.]+)%/
                    if (matcher) {
                        def coverage = matcher[0][1].toFloat()
                        echo "Line coverage: ${coverage}%"
                        if (coverage < 80.0) {
                            error("Coverage ${coverage}% is below the 80% threshold")
                        }
                    }
                }
            }
        }

        // ── 5. PUSH TO GHCR (main branch only) ───────────────────────
        stage('Push') {
            when { branch 'main' }
            steps {
                withCredentials([string(credentialsId: 'ghcr-token', variable: 'GHCR_TOKEN')]) {
                    sh "echo \${GHCR_TOKEN} | docker login ${REGISTRY} -u rha17369 --password-stdin"
                    script {
                        def services = env.SERVICES.split(' ')
                        services.each { svc ->
                            sh "docker push ${REPO}/${svc}:${BUILD_NUMBER}"
                            sh "docker push ${REPO}/${svc}:latest"
                        }
                    }
                }
            }
        }

        // ── 6. DEPLOY TO KUBERNETES (rolling update) ─────────────────
        stage('Deploy') {
            when { branch 'main' }
            steps {
                withCredentials([file(credentialsId: 'k3s-kubeconfig', variable: 'KUBECONFIG')]) {
                    sh """
                        kubectl apply -f k8s/ --namespace=${NAMESPACE} --recursive

                        kubectl rollout status deployment/auth-service         -n ${NAMESPACE} --timeout=120s
                        kubectl rollout status deployment/passenger-service    -n ${NAMESPACE} --timeout=120s
                        kubectl rollout status deployment/route-service        -n ${NAMESPACE} --timeout=120s
                        kubectl rollout status deployment/booking-service      -n ${NAMESPACE} --timeout=120s
                        kubectl rollout status deployment/ticket-service       -n ${NAMESPACE} --timeout=120s
                        kubectl rollout status deployment/notification-service -n ${NAMESPACE} --timeout=120s
                        kubectl rollout status deployment/api-gateway          -n ${NAMESPACE} --timeout=120s

                        kubectl get pods -n ${NAMESPACE}
                    """
                }
            }
        }
    }

    post {
        success {
            echo "Pipeline #${BUILD_NUMBER} completed — all Intercity237 services deployed successfully."
        }
        failure {
            echo "Pipeline #${BUILD_NUMBER} FAILED — rolling back all deployments..."
            withCredentials([file(credentialsId: 'k3s-kubeconfig', variable: 'KUBECONFIG')]) {
                sh """
                    kubectl rollout undo deployment/auth-service         -n ${NAMESPACE} || true
                    kubectl rollout undo deployment/passenger-service    -n ${NAMESPACE} || true
                    kubectl rollout undo deployment/route-service        -n ${NAMESPACE} || true
                    kubectl rollout undo deployment/booking-service      -n ${NAMESPACE} || true
                    kubectl rollout undo deployment/ticket-service       -n ${NAMESPACE} || true
                    kubectl rollout undo deployment/api-gateway          -n ${NAMESPACE} || true
                """
            }
        }
        always {
            script {
                def services = env.SERVICES.split(' ')
                services.each { svc ->
                    sh "docker rmi ${REPO}/${svc}:${BUILD_NUMBER} || true"
                }
            }
            echo "Build #${BUILD_NUMBER} cleanup complete."
        }
    }
}
