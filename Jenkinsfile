pipeline {
    agent any

    environment {
        REGISTRY   = 'ghcr.io'
        REPO       = 'ghcr.io/intercity237'
        NAMESPACE  = 'intercity237'
        SERVICES   = 'auth-service user-service dept-service notification-service api-gateway'
    }

    stages {

        stage('Checkout') {
            steps {
                checkout scm
                sh 'echo "Branch: ${BRANCH_NAME} — Build #${BUILD_NUMBER}"'
                sh 'git log --oneline -5'
            }
        }

        stage('Build All Services') {
            steps {
                script {
                    def services = env.SERVICES.split(' ')
                    services.each { svc ->
                        echo "Building ${svc}..."
                        sh "docker build -t ${REPO}/${svc}:${BUILD_NUMBER} ./${svc}"
                        sh "docker tag ${REPO}/${svc}:${BUILD_NUMBER} ${REPO}/${svc}:latest"
                    }
                }
            }
        }

        stage('Run Tests') {
            steps {
                sh """
                    docker run --rm \
                      -v \$(pwd)/auth-service/coverage:/var/www/html/coverage \
                      ${REPO}/auth-service:${BUILD_NUMBER} \
                      bash -c "cd /var/www/html && vendor/bin/phpunit --coverage-text --coverage-html coverage/ 2>&1 || true"
                """
            }
        }

        stage('Push Images') {
            when { branch 'main' }
            steps {
                withCredentials([string(credentialsId: 'ghcr-token', variable: 'GHCR_TOKEN')]) {
                    sh "echo \${GHCR_TOKEN} | docker login ${REGISTRY} -u intercity237 --password-stdin"
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

        stage('Deploy to Kubernetes') {
            when { branch 'main' }
            steps {
                withCredentials([file(credentialsId: 'k3s-kubeconfig', variable: 'KUBECONFIG')]) {
                    sh "kubectl apply -f k8s/ --namespace=${NAMESPACE} --recursive"
                    sh "kubectl rollout status deployment/auth-service    -n ${NAMESPACE} --timeout=120s"
                    sh "kubectl rollout status deployment/user-service    -n ${NAMESPACE} --timeout=120s"
                    sh "kubectl rollout status deployment/dept-service    -n ${NAMESPACE} --timeout=120s"
                    sh "kubectl get pods -n ${NAMESPACE}"
                }
            }
        }
    }

    post {
        success {
            echo "Pipeline #${BUILD_NUMBER} terminé avec succès — tous les services déployés."
        }
        failure {
            echo "Echec du pipeline #${BUILD_NUMBER} — rollback automatique."
            withCredentials([file(credentialsId: 'k3s-kubeconfig', variable: 'KUBECONFIG')]) {
                sh "kubectl rollout undo deployment/auth-service -n ${NAMESPACE} || true"
                sh "kubectl rollout undo deployment/user-service -n ${NAMESPACE} || true"
                sh "kubectl rollout undo deployment/dept-service -n ${NAMESPACE} || true"
            }
        }
        always {
            script {
                def services = env.SERVICES.split(' ')
                services.each { svc ->
                    sh "docker rmi ${REPO}/${svc}:${BUILD_NUMBER} || true"
                }
            }
        }
    }
}
